<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Ttree\ContentInsight\CrawlerProcessor\ProcessorInterface;
use Ttree\ContentInsight\Domain\Model\PresetDefinition;
use Ttree\ContentInsight\Domain\Model\UriDefinition;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Client\CurlEngineException;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManager;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\Arrays;

/**
 * Website Crawler
 *
 * @Flow\Scope("singleton")
 */
class Crawler {

	/**
	 * @Flow\Inject
	 * @var Downloader
	 */
	protected $downloader;

	/**
	 * @Flow\Inject
	 * @var UriService
	 */
	protected $uriService;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $processedUris = array();

	/**
	 * @var Uri
	 */
	protected $baseUri;

	/**
	 * @Flow\Inject(setting="crawling.maximumDepth")
	 * @var integer
	 */
	protected $maximumDepth;

	/**
	 * @Flow\Inject(setting="presets")
	 * @var array
	 */
	protected $presets;

	/**
	 * @var PresetDefinition
	 */
	protected $currentPreset = array();

	/**
	 * Initialize Object
	 */
	public function initializeObject() {
		$this->setPreset();
	}

	/**
	 * @param string $preset
	 * @return $this
	 */
	public function setPreset($preset = NULL) {
		$preset = $preset ?: '*';
		if (!isset($this->presets[$preset])) {
			throw new \InvalidArgumentException('Invalid preset', 1415749035);
		}

		if ($preset !== '*') {
			$currentPreset = Arrays::arrayMergeRecursiveOverrule($this->presets['*'], $this->presets[$preset]);
		} else {
			$currentPreset = $this->presets['*'];
		}

		$this->currentPreset = new PresetDefinition($currentPreset);
		$this->uriService->setInvalidUriPatterns($this->currentPreset->getInvalidUriPatterns());

		return $this;
	}

	/**
	 * @return PresetDefinition
	 */
	public function getCurrentPreset() {
		return $this->currentPreset;
	}

	/**
	 * @param string $baseUri
	 * @return array
	 */
	public function crawleFromBaseUri($baseUri) {
		$baseUri = new Uri(trim($baseUri, '/'));
		$this->baseUri = $baseUri;
		$this->crawleSingleUri($baseUri, $this->maximumDepth);
		$this->sortProcessedUris();

		return $this->processedUris;
	}

	/**
	 * @param string $uri
	 * @return UriDefinition
	 */
	public function getProcessedUri($uri) {
		$key = $this->uriService->getUriKey($uri);
		return Arrays::getValueByPath($this->processedUris, $key);
	}

	/**
	 * @param Uri $uri
	 * @param integer $depth
	 * @return void
	 */
	public function crawleSingleUri(Uri $uri, $depth) {
		if (!$this->checkIfCrawlable($uri)) {
			return;
		}

		$this->scheduleUriCrawling($uri);

		if ($this->getProcessedUriProperty($uri, 'external_link') === TRUE) {
			$this->log($uri, sprintf('URI "%s" skipped, external link', $uri));
			return;
		}

		$response = $this->downloader->get($uri);
		$this->setProcessedUriProperty($uri, 'status_code', $response->getStatusCode());

		if (strpos((string)$uri, (string)$this->baseUri) === FALSE) {
			$this->log($uri, sprintf('URI "%s" skipped, not a children of the base URI', $uri));
			return;
		}

		try {
			if ($response->getStatusCode() !== 200) {
				$this->log($uri, sprintf('URI "%s" skipped, invalid status code', $uri));
				return;
			}

			$contentType = $response->getHeader('Content-Type');
			$this->setProcessedUriProperty($uri, 'content_type', $contentType);
			if (strpos($contentType, 'text/html') === FALSE) {
				$this->log($uri, sprintf('URI "%s" skipped, invalid content type', $uri));
				return;
			}

			$content = new DomCrawler($response->getContent());

			foreach ($this->currentPreset->getProperties() as $propertyName => $propertyConfiguration) {
				if (!isset($propertyConfiguration['enabled']) || $propertyConfiguration['enabled'] !== TRUE) {
					continue;
				}
				$processor = $this->getProcessor($propertyName, $propertyConfiguration);
				$result = $processor->process($uri, $content, $this);
				if (is_array($result)) {
					$this->setProcessedUriProperties($uri, $result);
				} else {
					$this->setProcessedUriProperty($uri, $propertyName, $result);
				}
			}

			$this->setProcessedUriProperty($uri, 'visited', TRUE);
			$this->systemLogger->log(sprintf('URI "%s" visited', $uri));

			$this->processChildLinks($uri, $content, $depth);
		} catch (CurlEngineException $exception) {
			$this->log($uri, sprintf('URI "%s" skipped, curl error', $uri));
			$this->systemLogger->logException($exception);
		}
	}

	/**
	 * Create a tree
	 */
	protected function sortProcessedUris() {
		uasort($this->processedUris, function (UriDefinition $a, UriDefinition $b) {
			if ($a->getProperty('external_link') === $b->getProperty('external_link')) {
				return strnatcmp($a->getProperty('current_uri'), $b->getProperty('current_uri'));
			} else {
				return ($a->getProperty('external_link') < $b->getProperty('external_link')) ? -1 : 1;
			}
		});
	}

	/**
	 * @param string $propertyName
	 * @param array $propertyConfiguration
	 * @return ProcessorInterface
	 * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
	 */
	protected function getProcessor($propertyName, array $propertyConfiguration) {
		if (isset($propertyConfiguration['class']) && is_string($propertyConfiguration['class'])) {
			$processorClassName = $propertyConfiguration['class'];
		} else {
			$processorClassName = sprintf('Ttree\ContentInsight\CrawlerProcessor\%sProcessor', str_replace(' ', '', ucwords(str_replace('_', ' ', trim($propertyName)))));
		}
		/** @var ProcessorInterface $processor */
		$processor = $this->objectManager->get($processorClassName);
		if (!$processor instanceof ProcessorInterface) {
			throw new \InvalidArgumentException(sprintf('Processor "%s" must implement ProcessorInterface', $processorClassName), 1415749045);
		}

		return $processor;
	}

	/**
	 * @param Uri $uri
	 * @param DomCrawler $content
	 * @param integer $depth
	 */
	protected function processChildLinks(Uri $uri, DomCrawler $content, $depth) {
		foreach ($this->extractChildLinks($uri, $content) as $childLink) {
			try {
				if ($depth == 0) {
					$this->systemLogger->log(sprintf('Inventory exit, maximum nested level', $uri));
					return;
				}
				if (isset($childLink['dont_visit']) && $childLink['dont_visit'] === TRUE) {
					continue;
				}
				$childLinkUri = new Uri($childLink['original_urls']);
				$this->crawleSingleUri($childLinkUri, $depth - 1);
			} catch (\InvalidArgumentException $exception) {
				$this->systemLogger->logException($exception);
			}
		}
	}

	/**
	 * @param Uri $uri
	 * @param DomCrawler $content
	 * @return array
	 */
	protected function extractChildLinks(Uri $uri, DomCrawler $content) {
		$currentLinks = array();
		$content->filter('a')->each(function (DomCrawler $node) use (&$currentLinks) {
			$nodeLink = NULL;
			try {
				$nodeText = trim($node->text());
				$nodeLink = trim($node->attr('href'));
				$nodeLink = $this->uriService->normalizeUri($this->baseUri, $nodeLink);
				if ($nodeLink === '') {
					return;
				}
				$nodeKey = $this->uriService->getUriKey($nodeLink);

				if (!isset($this->processedUris[$nodeKey]) && !isset($currentLinks[$nodeKey])) {
					$currentLinks[$nodeKey]['original_urls'] = $nodeLink;
					$currentLinks[$nodeKey]['links_text'] = $nodeText;

					$nodeUri = new Uri($nodeLink);
					$crawlable = $this->checkIfCrawlable($nodeUri);
					if ($crawlable && !preg_match('@^http(s)?@', $nodeLink)) {
						$currentLinks[$nodeKey]['absolute_url'] = $this->baseUri . $nodeLink;
					} else {
						$currentLinks[$nodeKey]['absolute_url'] = $nodeLink;
					}

					if (!$crawlable) {
						$currentLinks[$nodeKey]['dont_visit'] = TRUE;
						$currentLinks[$nodeKey]['external_link'] = FALSE;
					} elseif ($this->uriService->checkIfExternal($this->baseUri, $currentLinks[$nodeKey]['absolute_url'])) {
						$currentLinks[$nodeKey]['external_link'] = TRUE;
					} else {
						$currentLinks[$nodeKey]['external_link'] = FALSE;
					}
					$currentLinks[$nodeKey]['visited'] = FALSE;

					$currentLinks[$nodeKey]['frequency'] = isset($currentLinks[$nodeKey]['frequency']) ? $currentLinks[$nodeKey]['frequency']++ : 1;
				}
			} catch (\InvalidArgumentException $exception) {
				$nodeKey = $this->uriService->getUriKey($nodeLink);
				$currentLinks[$nodeKey]['dont_visit'] = TRUE;
				$this->log($nodeLink, sprintf('URI "%s" skipped, %s', $nodeLink, $exception->getMessage()));
			}

		});

		if (isset($currentLinks[(string)$uri])) {
			$nodeKey = md5((string)$uri);
			$currentLinks[$nodeKey]['dont_visit'] = TRUE;
			$currentLinks[$nodeKey]['visited'] = TRUE;
		}

		return $currentLinks;
	}

	/**
	 * @param Uri $uri
	 */
	protected function scheduleUriCrawling(Uri $uri) {
		$uriString = trim($uri);
		if ($uriString === '') {
			return;
		}
		$this->setProcessedUriProperties($uri, array(
			'visited' => FALSE,
			'frequency' => 1,
			'depth' => $this->getUriDepth($uri),
			'external_link' => $this->uriService->checkIfExternal($this->baseUri, $uri),
			'current_uri' => $uriString,
		));
	}

	/**
	 * @param Uri $uri
	 * @return integer
	 */
	protected function getUriDepth(Uri $uri) {
		$uriParts = explode('//', (string)$uri);
		return count(explode('/', $uriParts[1])) - 1;
	}

	/**
	 * @param string $uri
	 * @return void
	 */
	protected function incrementFrequency($uri) {
		$frequency = $this->getProcessedUriProperty($uri, 'frequency');
		$this->setProcessedUriProperty($uri, 'frequency', $frequency + 1);
	}

	/**
	 * @param Uri $uri
	 * @return boolean
	 */
	protected function checkIfCrawlable(Uri $uri) {
		$key = $this->uriService->getUriKey($uri);
		if (isset($this->processedUris[$key])) {
			$this->incrementFrequency($uri);
			return FALSE;
		}

		if (!$this->uriService->isValidUri($uri)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param string $uri
	 * @param array $properties
	 */
	protected function setProcessedUriProperties($uri, array $properties) {
		foreach ($properties as $property => $value) {
			$this->setProcessedUriProperty($uri, $property, $value);
		}
	}

	/**
	 * @param string $uri
	 * @param string $propertyName
	 * @param string $propertyValue
	 * @return $this
	 */
	protected function setProcessedUriProperty($uri, $propertyName, $propertyValue) {
		$key = $this->uriService->getUriKey($uri);
		if (!isset($this->processedUris[$key])) {
			$this->processedUris[$key] = new UriDefinition();
		}
		/** @var UriDefinition $uriDefinition */
		$uriDefinition = $this->processedUris[$key];
		$uriDefinition->setProperty($propertyName, $propertyValue);

		return $this;
	}

	/**
	 * @param string $uri
	 * @param string $propertyName
	 * @return mixed
	 */
	protected function getProcessedUriProperty($uri, $propertyName) {
		$key = $this->uriService->getUriKey($uri);
		if (!isset($this->processedUris[$key])) {
			return NULL;
		}
		/** @var UriDefinition $uriDefinition */
		$uriDefinition = $this->processedUris[$key];

		return $uriDefinition->getProperty($propertyName);
	}

	/**
	 * @param string $uri
	 * @param string $message
	 */
	protected function log($uri, $message) {
		$this->setProcessedUriProperty($uri, 'remark', $message);
		$this->systemLogger->log($message);
	}
}