<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Uri;

/**
 * Web Page Downloader
 *
 * @Flow\Scope("singleton")
 */
class UriService {

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
		if (!preg_match("@^http(s)?@", $uri)) {
			$uri = $baseUri->getScheme() . '://' . $baseUri->getHost() . $uri;
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
	 * Return TRUE if the given URI is an external URI
	 *
	 * @param Uri $baseUri
	 * @param string $uri
	 * @return boolean
	 */
	public function checkIfExternal(Uri $baseUri, $uri) {
		if (preg_match(sprintf('@http(s)?\://%s@', $baseUri->getHost()), $uri)) { //base url is not the first portion of the url
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