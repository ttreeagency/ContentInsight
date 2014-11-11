<?php
namespace Ttree\ContentInsight\Aspects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Flow\Http\Client\Browser;
use TYPO3\Flow\Http\Client\CurlEngine;
use TYPO3\Flow\Http\Client\InfiniteRedirectionException;
use TYPO3\Flow\Http\Response;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class DownloaderCacheAspect {

	/**
	 * @var StringFrontend
	 */
	protected $cache;

	/**
	 * @param StringFrontend $cache
	 */
	public function setCache(StringFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * @Flow\Around("setting(Ttree.ContentInsight.cache.enabled) && method(Ttree\ContentInsight\Service\Downloader->get())")
	 * @param JoinPointInterface $joinPoint
	 * @return Response
	 */
	public function cacheHttpRequest(JoinPointInterface $joinPoint) {
		$uri = $joinPoint->getMethodArgument('uri');
		$cacheKey = md5($uri);
		if ($this->cache->has($cacheKey)) {
			$rawHttpResponse = $this->cache->get($cacheKey);
			$response = Response::createFromRaw($rawHttpResponse);
			return $response;
		}
		/** @var Response $response */
		$response = $joinPoint->getAdviceChain()->proceed($joinPoint);

		$this->cache->set($cacheKey, $this->convertResponseToRawResponse($response));

		return $response;
	}

	/**
	 * @param Response $response
	 * @return string
	 */
	protected function convertResponseToRawResponse(Response $response) {
		$rawHttpResponse = '';
		foreach ($response->renderHeaders() as $header) {
			$rawHttpResponse .= $header . "\r\n";
		}
		$rawHttpResponse .= "\r\n\r\n";
		$rawHttpResponse .= $response->getContent();

		return $rawHttpResponse;
	}

}