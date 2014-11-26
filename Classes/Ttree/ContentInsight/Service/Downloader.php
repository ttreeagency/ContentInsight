<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Client\Browser;
use TYPO3\Flow\Http\Client\CurlEngine;
use TYPO3\Flow\Http\Client\InfiniteRedirectionException;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;

/**
 * Web Page Downloader
 *
 * @Flow\Scope("singleton")
 */
class Downloader {

	/**
	 * @var Browser
	 */
	protected $browser;

	/**
	 * Initialize object
	 */
	public function __construct() {
		$this->browser = new Browser();
		$this->browser->setRequestEngine(new CurlEngine());
	}

	/**
	 * @param Uri $uri
	 * @param boolean $followRedirects
	 * @return Response
	 * @throws InfiniteRedirectionException
	 */
	public function get(Uri $uri, $followRedirects = FALSE) {
		$this->browser->setFollowRedirects($followRedirects);
		$response = $this->browser->request($uri);

		return $response;
	}
}