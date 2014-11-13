<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Ttree\ContentInsight\Domain\Model\PresetDefinition;
use Ttree\ContentInsight\Domain\Model\UriDefinition;
use Ttree\ContentInsight\Service\CrawlerProcessor\ProcessorInterface;
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
	protected $processedUri = array();

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
	 * @var integer
	 */
	protected $presets = 100;

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

		return $this;
	}

	/**
	 * @param string $baseUri
	 * @return array
	 */
	public function crawleFromBaseUri($baseUri) {
		$baseUri = new Uri(trim($baseUri, '/'));
		$this->baseUri = $baseUri;
		$this->crawleSingleUri($baseUri, $this->maximumDepth);

		return $this->processedUri;
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

		if ($this->getProcessedUriProperty($uri, 'external_link') === TRUE) {
			$this->systemLogger->log(sprintf('URI "%s" skipped, external link', $uri));
			return;
		}

		if (strpos((string)$uri, (string)$this->baseUri) === FALSE) {
			$this->systemLogger->log(sprintf('URI "%s" skipped, not a children of the base URI', $uri));
			return;
		}

		try {
			$this->scheduleUriCrawling($uri);

			$response = $this->downloader->get($uri);
			$this->setProcessedUriProperty($uri, 'status_code', $response->getStatusCode());
			if ($response->getStatusCode() !== 200) {
				$this->systemLogger->log(sprintf('URI "%s" skipped, invalid status code', $uri));
				return;
			}

			$contentType = $response->getHeader('Content-Type');
			$this->setProcessedUriProperty($uri, 'content_type', $contentType);
			if (strpos($contentType, 'text/html') === FALSE) {
				$this->systemLogger->log(sprintf('URI "%s" skipped, invalid content type', $uri));
				return;
			}

			$content = new DomCrawler($response->getContent());

			foreach ($this->currentPreset->getProperties() as $propertyName) {
				$processor = $this->getProcessorByPropertyName($propertyName);
				$result = $processor->process($uri, $content);
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
			$this->systemLogger->log(sprintf('URI "%s" skipped, curl error', $uri));
			$this->systemLogger->logException($exception);
		}
	}

	/**
	 * @param string $propertyName
	 * @return ProcessorInterface
	 * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
	 */
	protected function getProcessorByPropertyName($propertyName) {
		$processorClassName = sprintf('Ttree\ContentInsight\Service\CrawlerProcessor\%sProcessor', str_replace(' ', '', ucwords(str_replace('_', ' ', trim($propertyName)))));
		/** @var ProcessorInterface $processor */
		$processor = $this->objectManager->get($processorClassName);
		if (!$processor instanceof ProcessorInterface) {
			throw new \InvalidArgumentException('Processor must implement ProcessorInterface', 1415749045);
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
				$this->crawleSingleUri($childLinkUri, --$depth);
			} catch (\InvalidArgumentException $exception) {

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
			try {
				$nodeText = trim($node->text());
				$nodeLink = $this->uriService->normalizeUri($this->baseUri, $node->attr('href'));
				$nodeKey = $this->uriService->getUriKey($nodeLink);

				if (!isset($this->processedUri[$nodeKey]) && !isset($currentLinks[$nodeKey])) {
					$currentLinks[$nodeKey]['original_urls'] = $nodeLink;
					$currentLinks[$nodeKey]['links_text'] = $nodeText;

					$nodeUri = new Uri($nodeLink);
					$crawlable = $this->checkIfCrawlable($nodeUri);
					if ($crawlable && !preg_match("@^http(s)?@", $nodeLink)) {
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
		$this->generatePageId($uri);
		$this->setProcessedUriProperties($uri, array(
			'visited' => FALSE,
			'frequency' => 1,
			'external_link' => $this->uriService->checkIfExternal($this->baseUri, $uri),
			'current_uri' => (string)$uri,
		));
	}

	/**
	 * @param Uri $uri
	 */
	protected function generatePageId(Uri $uri) {
		$this->setProcessedUriProperty($uri, 'id', count($this->processedUri) + 1);
	}

	/**
	 * @param string $uri
	 * @return void
	 */
	protected function incrementFrequency($uri) {
		$frequency = $this->getProcessedUriProperty($uri, 'frequency');
		$this->setProcessedUriProperty($uri, 'frequency', ++$frequency);
	}

	/**
	 * @param Uri $uri
	 * @return boolean
	 */
	protected function checkIfCrawlable(Uri $uri) {
		if (isset($this->processedUri[(string)$uri])) {
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
		if (!isset($this->processedUri[$key])) {
			$this->processedUri[$key] = new UriDefinition();
		}
		/** @var UriDefinition $uriDefinition */
		$uriDefinition = $this->processedUri[$key];
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
		if (!isset($this->processedUri[$key])) {
			return NULL;
		}
		/** @var UriDefinition $uriDefinition */
		$uriDefinition = $this->processedUri[$key];

		return $uriDefinition->getProperty($propertyName);
	}
}