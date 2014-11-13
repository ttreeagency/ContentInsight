<?php
namespace Ttree\ContentInsight\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Ttree\ContentInsight\Service\Crawler;
use TYPO3\Flow\Annotations as Flow;

/**
 * Website Crawler
 *
 * @Flow\Scope("singleton")
 */
class MetaDescriptionProcessor implements ProcessorInterface {

	/**
	 * @param string $uri
	 * @param DomCrawler $content
	 * @param Crawler $crawler
	 * @return string
	 */
	public function process($uri, DomCrawler $content, Crawler $crawler) {
		$description = NULL;
		try {
			$description = $content->filterXPath('html/head/meta[@name="description"]/@content')->text();
		} catch (\InvalidArgumentException $exception) {

		}

		return $description;
	}


}