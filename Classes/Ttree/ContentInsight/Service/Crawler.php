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
	protected $maximumCrawlingDepth;

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
		$this->crawleSingleUri($baseUri, $this->maximumCrawlingDepth);
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
	 * @param integer $crawlingDepth
	 * @return void
	 */
	public function crawleSingleUri(Uri $uri, $crawlingDepth = 0) {
		if (!$this->checkIfCrawlable($uri)) {
			return;
		}

		$uri = $this->scheduleUriCrawling($uri);

		if ($uri->getProperty('external_link') === TRUE) {
			if ($this->currentPreset->getInventoryConfiguration()->skipExternalUris()) {
				$this->log($uri, sprintf('URI "%s" skipped, external link', $uri));
				$this->unscheduleUriCrawling($uri->getUri());
			} else {
				$response = $this->downloader->get($uri);
				$uri->setProperty('status_code', $response->getStatusCode());
			}
			return;
		}

		if ($uri->isChildrenOf($this->baseUri) === FALSE) {
			$this->log($uri, sprintf('URI "%s" skipped, not a children of the base URI', $uri));
			$this->unscheduleUriCrawling($uri->getUri());
			return;
		}

		// Follow redirect only for the Base URL
		$response = $this->downloader->get($uri->getUri(), (string)$this->baseUri === (string)$uri);
		$statusCode = $response->getStatusCode();
		$uri->setProperty('status_code', $statusCode);

		try {
			if ($statusCode !== 200) {
				$this->log($uri, sprintf('URI "%s" skipped, non 20x status code (%s)', $uri, $statusCode));
				return;
			}

			$contentType = $response->getHeader('Content-Type');
			$uri->setProperty('content_type', $contentType);
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
				try {
					$result = $processor->process($uri, $content, $this);
				} catch (\Exception $exception) {
					$result = sprintf('Processor "%s" failed with message: %s/%s', get_class($processor), $exception->getCode(), $exception->getMessage());
				}
				if (is_array($result)) {
					$uri->setProperties($result);
				} else {
					$uri->setProperty($propertyName, $result);
				}
			}

			$uri->setProperty('visited', TRUE);
			$this->systemLogger->log(sprintf('URI "%s" visited', $uri));

			$this->processChildLinks($uri, $content, $crawlingDepth);
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
	 * @param UriDefinition $uri
	 * @param DomCrawler $content
	 * @param integer $crawlingDepth
	 */
	protected function processChildLinks(UriDefinition $uri, DomCrawler $content, $crawlingDepth) {
		foreach ($this->extractChildLinks($uri, $content) as $uriDefinition) {
			/** @var UriDefinition $uriDefinition */
			try {
				if ($crawlingDepth == 0) {
					$this->systemLogger->log(sprintf('Inventory exit, maximum nested level', $uri));
					return;
				}
				if ($uriDefinition->getProperty('dont_visit') === TRUE) {
					continue;
				}
				$childLinkUri = $uriDefinition->getUri();
				$this->crawleSingleUri($childLinkUri, $crawlingDepth - 1);
			} catch (\InvalidArgumentException $exception) {
				$this->systemLogger->logException($exception);
			}
		}
	}

	/**
	 * @param UriDefinition $uri
	 * @param DomCrawler $content
	 * @return array
	 */
	protected function extractChildLinks(UriDefinition $uri, DomCrawler $content) {
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
					$nodeUri = new Uri($nodeLink);
					$nodeUriDefinition = new UriDefinition($nodeUri, array(
						'links_text' => $nodeText
					));
					$crawlable = $this->checkIfCrawlable($nodeUriDefinition->getUri());
					if ($crawlable && !preg_match('@^http(s)?@', $nodeLink)) {
						$nodeUriDefinition->setProperty('absolute_url', $this->baseUri . $nodeLink);
					} else {
						$nodeUriDefinition->setProperty('absolute_url', $nodeLink);
					}

					if (!$crawlable) {
						$nodeUriDefinition->setProperty('dont_visit', TRUE);
						$nodeUriDefinition->setProperty('external_link', FALSE);
					} elseif ($this->uriService->checkIfExternal($this->baseUri, $nodeUriDefinition->getProperty('absolute_url'))) {
						$nodeUriDefinition->setProperty('external_link', TRUE);
					} else {
						$nodeUriDefinition->setProperty('external_link', FALSE);
					}
					$nodeUriDefinition->setProperty('visited', FALSE);
					$nodeUriDefinition->incrementFrequency();

					$currentLinks[$nodeKey] = $nodeUriDefinition;
				}
			} catch (\InvalidArgumentException $exception) {
				if (isset($nodeUriDefinition) && $nodeUriDefinition instanceof UriDefinition) {
					$nodeUriDefinition->setProperty('dont_visit', TRUE);
					$this->log($nodeUriDefinition, sprintf('URI "%s" skipped, %s', $nodeLink, $exception->getMessage()));
				}
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
	 * @return UriDefinition
	 */
	protected function scheduleUriCrawling(Uri $uri) {
		$uriString = trim($uri);
		if ($uriString === '') {
			return;
		}
		$uriKey = $this->uriService->getUriKey($uri);
		$uriDefinition = new UriDefinition($uri, array(
			'visited' => FALSE,
			'frequency' => 1,
			'external_link' => $this->uriService->checkIfExternal($this->baseUri, $uri),
			'current_uri' => $uriString,
		));
		$uriDefinition->getUriDepth($this->baseUri);

		$this->processedUris[$uriKey] = $uriDefinition;

		return $this->processedUris[$uriKey];
	}

	/**
	 * @param Uri $uri
	 */
	protected function unscheduleUriCrawling(Uri $uri) {
		$uriString = trim($uri);
		if ($uriString === '') {
			return;
		}
		$uriKey = $this->uriService->getUriKey($uri);
		unset($this->processedUris[$uriKey]);
	}



	/**
	 * @param Uri $uri
	 * @return boolean
	 */
	protected function checkIfCrawlable(Uri $uri) {
		$key = $this->uriService->getUriKey($uri);
		if (isset($this->processedUris[$key])) {
			/** @var UriDefinition $uriDefinition */
			$uriDefinition = $this->processedUris[$key];
			$uriDefinition->incrementFrequency();
			return FALSE;
		}

		if (!$this->uriService->isValidUri($uri)) {
			$this->unscheduleUriCrawling($uri);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param UriDefinition $uri
	 * @param string $message
	 */
	protected function log(UriDefinition $uri, $message) {
		$uri->setProperty('remark', $message);
		$this->systemLogger->log($message);
	}
}