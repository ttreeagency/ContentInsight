<?php
namespace Ttree\ContentInsight\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Ttree\ContentInsight\Domain\Model\HtmlDocument;
use Ttree\ContentInsight\Service\Crawler;
use TYPO3\Flow\Annotations as Flow;

/**
 * Website Crawler
 *
 * @Flow\Scope("singleton")
 */
interface ProcessorInterface {

	/**
	 * @param string $uri
	 * @param HtmlDocument $document
	 * @param Crawler $crawler
	 * @return mixed
	 */
	public function process($uri, HtmlDocument $document, Crawler $crawler);
}