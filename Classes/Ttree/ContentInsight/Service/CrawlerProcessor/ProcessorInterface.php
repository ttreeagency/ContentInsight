<?php
namespace Ttree\ContentInsight\Service\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\Flow\Annotations as Flow;

/**
 * Website Crawler
 *
 * @Flow\Scope("singleton")
 */
interface ProcessorInterface {

	/**
	 * @param string $uri
	 * @param Crawler $content
	 * @return mixed
	 */
	public function process($uri, Crawler $content);
}