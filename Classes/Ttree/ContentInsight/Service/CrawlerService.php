<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Client\CurlEngineException;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * Web Crawler Service
 * @Flow\Scope("singleton")
 */
class CrawlerService {

	/**
	 * @Flow\Inject
	 * @var Downloader
	 */
	protected $downloader;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

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
	protected $maximumDepth = 100;

	/**
	 * @param string $baseUri
	 * @return array
	 */
	public function processFromBaseUri($baseUri) {
		$baseUri = new Uri(trim($baseUri, '/'));
		$this->baseUri = $baseUri;
		$this->processSingleUri($baseUri, $this->maximumDepth);

		return $this->processedUri;
	}

	/**
	 * @param Uri $uri
	 * @param integer $depth
	 */
	public function processSingleUri(Uri $uri, $depth) {
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

			$content = new Crawler($response->getContent());

			$this->extractTitle($uri, $content);
			$this->extractMetaDescription($uri, $content);
			$this->extractMetaKeywords($uri, $content);
			$this->extractFirstLevelHeader($uri, $content);

			$this->setVisited($uri);
			$this->systemLogger->log(sprintf('URI "%s" visited', $uri));

			$this->processChildLinks($uri, $content, $depth);
		} catch (CurlEngineException $exception) {
			$this->systemLogger->log(sprintf('URI "%s" skipped, curl error', $uri));
			$this->systemLogger->logException($exception);
		}
	}

	/**
	 * @param Uri $uri
	 * @param Crawler $content
	 * @param integer $depth
	 */
	public function processChildLinks(Uri $uri, Crawler $content, $depth) {
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
				$this->processSingleUri($childLinkUri, --$depth);
			} catch (\InvalidArgumentException $exception) {

			}
		}
	}

	/**
	 * @param Uri $uri
	 */
	public function setVisited(Uri $uri) {
		$this->setProcessedUriProperty($uri, 'visited', TRUE);
	}

	/**
	 * @param Uri $uri
	 * @param Crawler $content
	 * @return string
	 */
	public function extractTitle(Uri $uri, Crawler $content) {
		try {
			$title = $content->filterXPath('html/head/title')->text();
			$this->setProcessedUriProperties($uri, array(
				'page_title' => $title,
				'navigation_title' => 'TODO'
			));
		} catch (\InvalidArgumentException $exception) {
			$this->setProcessedUriProperties($uri, array(
				'page_title' => '',
				'navigation_title' => ''
			));
		}
	}

	/**
	 * @param Uri $uri
	 * @param Crawler $content
	 */
	public function extractMetaDescription(Uri $uri, Crawler $content) {
		try {
			$this->setProcessedUriProperty($uri, 'meta_description', $content->filterXPath('html/head/meta[@name="description"]/@content')->text());
		} catch (\InvalidArgumentException $exception) {
			$this->setProcessedUriProperty($uri, 'meta_description', '');
		}
	}

	/**
	 * @param Uri $uri
	 * @param Crawler $content
	 */
	public function extractMetaKeywords(Uri $uri, Crawler $content) {
		try {
			$this->setProcessedUriProperty($uri, 'meta_keywords', $content->filterXPath('html/head/meta[@name="keywords"]/@content')->text());
		} catch (\InvalidArgumentException $exception) {
			$this->setProcessedUriProperty($uri, 'meta_keywords', '');
		}
	}

	/**
	 * @param Uri $uri
	 * @param Crawler $content
	 */
	public function extractFirstLevelHeader(Uri $uri, Crawler $content) {
		$headers = $content->filter('h1');
		$firstLevelHeaderCounter = $headers->count();
		$this->setProcessedUriProperty($uri, 'first_level_header_count', $firstLevelHeaderCounter);
		if ($firstLevelHeaderCounter > 0) {
			$contents = array();
			$headers->each(function (Crawler $node, $i) use (&$contents) {
				$contents[$i] = trim($node->text());
			});
			$this->setProcessedUriProperty($uri, 'first_level_header_content', implode($contents, '; '));
		}
	}

	/**
	 * @param Uri $uri
	 * @param Crawler $content
	 * @return array
	 */
	protected function extractChildLinks(Uri $uri, Crawler $content) {
		$currentLinks = array();
		$content->filter('a')->each(function (Crawler $node) use (&$currentLinks) {
			try {
				$nodeText = trim($node->text());
				$nodeLink = $this->normalizeUri($node->attr('href'));
				$nodeKey = $this->getUriKey($nodeLink);

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
					} elseif ($this->checkIfExternal($currentLinks[$nodeKey]['absolute_url'])) {
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
	 * Return TRUE if the given URI is an external URI
	 *
	 * @param string $uri
	 * @return boolean
	 */
	public function checkIfExternal($uri) {
		if (preg_match(sprintf('@http(s)?\://%s@', $this->baseUri->getHost()), $uri)) { //base url is not the first portion of the url
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Normalize URI, transform relative URL to absolute
	 *
	 * @param string $uri
	 * @return string
	 */
	public function normalizeUri($uri) {
		if (!$this->isValidUri($uri)) {
			throw new \InvalidArgumentException('Invalid URI, unable to normalize', 1415749025);
		}
		if (!preg_match("@^http(s)?@", $uri)) {
			$uri = $this->baseUri->getScheme() . '://' . $this->baseUri->getHost() . $uri;
		}

		return $uri;
	}

	/**
	 * @param string $uri
	 * @return boolean
	 */
	public function isValidUri($uri) {
		$stopLinks = array(
			'@^javascript\:void\(0\)$@',
			'@^mailto\:.*@',
			'@^#.*@',
		);

		foreach ($stopLinks as $pattern) {
			if (preg_match($pattern, (string)$uri)) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param Uri $uri
	 */
	protected function scheduleUriCrawling(Uri $uri) {
		$this->generatePageId($uri);
		$this->setProcessedUriProperties($uri, array(
			'visited' => FALSE,
			'frequency' => 1,
			'external_link' => $this->checkIfExternal($uri),
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

		if (!$this->isValidUri($uri)) {
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
	 * @param string $property
	 * @param string $value
	 */
	protected function setProcessedUriProperty($uri, $property, $value) {
		$this->processedUri = Arrays::setValueByPath($this->processedUri, array($this->getUriKey($uri), $property), $value);
	}

	/**
	 * @param string $uri
	 * @param string $property
	 * @return mixed
	 */
	protected function getProcessedUriProperty($uri, $property) {
		return Arrays::getValueByPath($this->processedUri, array($this->getUriKey($uri), $property));
	}

	/**
	 * @param string $uri
	 * @return string
	 */
	protected function getUriKey($uri) {
		return md5(trim(str_replace(array('http://', 'https://'), '', (string)$uri), '/'));
	}
}