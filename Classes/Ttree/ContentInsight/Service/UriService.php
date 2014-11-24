<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * Web Page Downloader
 *
 * @Flow\Scope("singleton")
 */
class UriService {

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $invalidUriPatterns = array();

	/**
	 * @param array $invalidUriPatterns
	 */
	public function setInvalidUriPatterns(array $invalidUriPatterns) {
		$this->invalidUriPatterns = $invalidUriPatterns;
	}

	/**
	 * Normalize URI, transform relative URL to absolute
	 *
	 * @param Uri $baseUri
	 * @param string $uri
	 * @return string
	 */
	public function normalizeUri(Uri $baseUri, $uri) {
		if (!$this->isValidUri($uri)) {
			throw new \InvalidArgumentException('Invalid URI, unable to normalize', 1415749025);
		}
		if (!preg_match('@^http(s)?@', $uri)) {
			$uri = $baseUri->getScheme() . '://' . $baseUri->getHost() . $uri;
		}

		return trim($uri);
	}

	/**
	 * @param Uri|string $uri
	 * @return boolean
	 */
	public function isValidUri($uri) {
		if (!$uri instanceof Uri) {
			$uri = new Uri($uri);
		}
		foreach ($this->invalidUriPatterns as $patternConfiguration) {
			if (!isset($patternConfiguration['pattern'])) {
				throw new \InvalidArgumentException('Missing pattern', 1415878090);
			}
			if (preg_match($patternConfiguration['pattern'], (string)$uri) === 1) {
				if (isset($patternConfiguration['message']) && is_string($patternConfiguration['message'])) {
					$this->systemLogger->log(sprintf('URI "%s" skipped, %s', $uri, $patternConfiguration['message']));
				}
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Return TRUE if the given URI is an external URI
	 *
	 * @param Uri $baseUri
	 * @param string $uri
	 * @return boolean
	 */
	public function checkIfExternal(Uri $baseUri, $uri) {
		if (preg_match(sprintf('@http(s)?\://%s@', $baseUri->getHost()), $uri)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * @param string $uri
	 * @return string
	 */
	public function getUriKey($uri) {
		return md5(trim(str_replace(array('http://', 'https://'), '', (string)$uri), '/'));
	}

}