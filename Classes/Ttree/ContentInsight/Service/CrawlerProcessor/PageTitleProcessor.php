<?php
namespace Ttree\ContentInsight\Service\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use TYPO3\Flow\Annotations as Flow;

/**
 * Website Crawler
 *
 * @Flow\Scope("singleton")
 */
class PageTitleProcessor implements ProcessorInterface {

	/**
	 * @param string $uri
	 * @param DomCrawler $content
	 * @return string
	 */
	public function process($uri, DomCrawler $content) {
		$title = NULL;
		try {
			$title = $content->filterXPath('html/head/title')->text();
		} catch (\InvalidArgumentException $exception) {

		}

		return $title;
	}


}