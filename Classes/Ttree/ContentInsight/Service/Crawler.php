<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Ttree\ContentInsight\CrawlerProcessor\ProcessorInterface;
use Ttree\ContentInsight\Domain\Model\HtmlDocument;
use Ttree\ContentInsight\Domain\Model\PresetDefinition;
use Ttree\ContentInsight\Domain\Model\UriDefinition;
use Ttree\ContentInsight\Utility\ResponseUtility;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Client\CurlEngineException;
use TYPO3\Flow\Http\Client\InfiniteRedirectionException;
use TYPO3\Flow\Http\Response;
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
	 * @var string
	 */
	protected $encoding;

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
	 * @param string $encoding
	 * @return $this
	 */
	public function setEncoding($encoding) {
		if ($encoding !== NULL && !in_array(strtolower($encoding), array_map('strtolower', mb_list_encodings()))) {
			throw new \InvalidArgumentException('Unsupported encoding', 1417822478);
		}
		$this->encoding = $encoding;
		return $this;
	}

	/**
	 * @param string $baseUri
	 * @return array
	 */
	public function crawlFromBaseUri($baseUri) {
		$baseUri = new Uri(trim($baseUri, '/'));
		$this->baseUri = $baseUri;
		$this->crawlSingleUri($baseUri, $this->maximumCrawlingDepth);
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
	protected function crawlSingleUri(Uri $uri, $crawlingDepth = 0) {
		if (!$this->checkIfCrawlable($uri)) {
			return;
		}

		$uri = $this->scheduleUriCrawling($uri);

		if ($uri->getProperty('externalLink') === TRUE && $this->currentPreset->getInventoryConfiguration()->skipExternalUris()) {
			$this->unscheduleUriCrawling($uri->getUri());
			return;
		}

		if ($uri->getProperty('externalLink') === FALSE && $uri->isChildrenOf($this->baseUri) === FALSE) {
			$this->log($uri, sprintf('URI "%s" skipped, not a children of the base URI', $uri));
			$this->unscheduleUriCrawling($uri->getUri());
			return;
		}

		try {
			// Follow redirect only for the Base URL
			$response = $this->downloader->get($uri->getUri(), (string)$this->baseUri === (string)$uri);

			if ($this->canHandleResponse($uri, $response) === FALSE) {
				$uri->markHasVisited();
				return;
			}

			$this->systemLogger->log($uri, sprintf('Process "%s" ...', $uri));
			$contentType = $response->getHeader('Content-Type');
			$uri->setProperty('contentType', $contentType);
			if (strpos($contentType, 'text/html') === FALSE) {
				$this->log($uri, sprintf('URI "%s" skipped, invalid content type', $uri));
				return;
			}

			$content = $response->getContent();
			$uri->setProperty('content_hash', md5($content));

			$content = new HtmlDocument($response->getContent(), $this->encoding);

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

			$uri->markHasVisited();
			$this->systemLogger->log(sprintf('URI "%s" visited', $uri));

			$this->processChildLinks($uri, $content, $crawlingDepth);
		} catch (InfiniteRedirectionException $exception) {
			$this->log($uri, 'Infinite Redirection');
			$uri->markHasVisited();
		} catch (CurlEngineException $exception) {
			$this->log($uri, sprintf('URI "%s" skipped, curl error: %s', $uri, $exception->getMessage()));
			$this->systemLogger->logException($exception);
		}
	}

	/**
	 * @param UriDefinition $uri
	 * @param Response $response
	 * @return bool
	 */
	protected function canHandleResponse(UriDefinition $uri, Response $response) {
		$continueProcessing = TRUE;

		$statusCode = $response->getStatusCode();
		$uri->setProperty('statusCode', $statusCode);

		if (ResponseUtility::isRedirect($response)) {
			try {
				// Browser support Location with or without uppercase
				$location = $response->getHeader('Location') ?: $response->getHeader('location');
				$location = $this->uriService->normalizeUri($this->baseUri, $location);
				$locationUri = new Uri($location);
				$rediretResponse = $this->downloader->get($locationUri, TRUE);
				$this->log($uri, sprintf('Skipped, redirection to "%s" (%s)', $locationUri, $rediretResponse->getStatusCode()));
				$uri->setProperty('redirectLocation', (string)$locationUri);
				$uri->setProperty('redirectStatusCode', $statusCode);
			} catch (\InvalidArgumentException $exception) {
				$this->log($uri, 'Skipped, redirection to unknown location');
			}
			return FALSE;
		} elseif (!ResponseUtility::isSuccessful($response)) {
			$this->log($uri, sprintf('Skipped, non 20x status code (%s)', $statusCode));
			return FALSE;
		} elseif ($uri->getProperty('externalLink') === TRUE) {
			$this->log($uri, sprintf('Skipped, external link', $statusCode));
			return FALSE;
		}

		return $continueProcessing;
	}

	/**
	 * Create a tree
	 */
	protected function sortProcessedUris() {
		uasort($this->processedUris, function (UriDefinition $a, UriDefinition $b) {
			if ($a->getProperty('externalLink') === $b->getProperty('externalLink')) {
				return strnatcmp($a->getProperty('currentUri'), $b->getProperty('currentUri'));
			} else {
				return ($a->getProperty('externalLink') < $b->getProperty('externalLink')) ? -1 : 1;
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
	 * @param HtmlDocument $document
	 * @param integer $crawlingDepth
	 */
	protected function processChildLinks(UriDefinition $uri, HtmlDocument $document, $crawlingDepth) {
		foreach ($this->extractChildLinks($uri, $document) as $uriDefinition) {
			/** @var UriDefinition $uriDefinition */
			try {
				if ($crawlingDepth == 0) {
					$this->systemLogger->log(sprintf('Inventory exit, maximum nested level', $uri));
					return;
				}
				if ($uriDefinition->getProperty('doNotVisit') === TRUE) {
					continue;
				}
				$childLinkUri = $uriDefinition->getUri();
				$this->crawlSingleUri($childLinkUri, $crawlingDepth - 1);
			} catch (\InvalidArgumentException $exception) {
				$this->systemLogger->logException($exception);
			}
		}
	}

	/**
	 * @param UriDefinition $uri
	 * @param HtmlDocument $document
	 * @return array
	 */
	protected function extractChildLinks(UriDefinition $uri, HtmlDocument $document) {
		$currentLinks = array();
		$document->getCrawler()->filter('a')->each(function (DomCrawler $node) use (&$currentLinks) {
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
						'linkText' => $nodeText
					));
					$crawlable = $this->checkIfCrawlable($nodeUriDefinition->getUri());
					if ($crawlable && !preg_match('@^http(s)?@', $nodeLink)) {
						$nodeUriDefinition->setProperty('absoluteUrl', $this->baseUri . $nodeLink);
					} else {
						$nodeUriDefinition->setProperty('absoluteUrl', $nodeLink);
					}

					if (!$crawlable) {
						$nodeUriDefinition->setProperty('doNotVisit', TRUE);
						$nodeUriDefinition->setProperty('externalLink', FALSE);
					} elseif ($this->uriService->checkIfExternal($this->baseUri, $nodeUriDefinition->getProperty('absoluteUrl'))) {
						$nodeUriDefinition->setProperty('externalLink', TRUE);
					} else {
						$nodeUriDefinition->setProperty('externalLink', FALSE);
					}
					$nodeUriDefinition->setProperty('visited', FALSE);
					$nodeUriDefinition->incrementFrequency();

					$currentLinks[$nodeKey] = $nodeUriDefinition;
				}
			} catch (\InvalidArgumentException $exception) {
				if (isset($nodeUriDefinition) && $nodeUriDefinition instanceof UriDefinition) {
					$nodeUriDefinition->setProperty('doNotVisit', TRUE);
					$this->log($nodeUriDefinition, sprintf('URI "%s" skipped, %s', $nodeLink, $exception->getMessage()));
				}
			}

		});

		if (isset($currentLinks[(string)$uri])) {
			$nodeKey = md5((string)$uri);
			$currentLinks[$nodeKey]['doNotVisit'] = TRUE;
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
			'externalLink' => $this->uriService->checkIfExternal($this->baseUri, $uri),
			'currentUri' => $uriString,
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