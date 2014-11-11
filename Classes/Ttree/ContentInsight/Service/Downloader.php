<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Client\Browser;
use TYPO3\Flow\Http\Client\CurlEngine;
use TYPO3\Flow\Http\Client\InfiniteRedirectionException;
use TYPO3\Flow\Http\Response;

/**
 * Web Crawler Service
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
	 * @param string $uri
	 * @return Response
	 * @throws InfiniteRedirectionException
	 */
	public function get($uri) {
		$uri = $this->normalizeLink($uri);
		$response = $this->browser->request($uri);

		return $response;
	}

	/**
	 * @param string $uri
	 * @return mixed
	 */
	protected function normalizeLink($uri) {
		return preg_replace('@#.*$@', '', $uri);
	}
}