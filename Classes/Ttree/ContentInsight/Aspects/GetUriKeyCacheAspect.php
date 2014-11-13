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
class GetUriKeyCacheAspect {

	/**
	 * @var array
	 */
	protected $cache;

	/**
	 * @Flow\Around("method(Ttree\ContentInsight\Service\UriService->getUriKey())")
	 * @param JoinPointInterface $joinPoint
	 * @return Response
	 */
	public function cacheHttpRequest(JoinPointInterface $joinPoint) {
		$uri = (string)$joinPoint->getMethodArgument('uri');
		if (isset($this->cache[$uri])) {
			return $this->cache[$uri];
		}
		$uriKey = $joinPoint->getAdviceChain()->proceed($joinPoint);

		$this->cache[$uri] = $uriKey;

		return $uriKey;
	}

}