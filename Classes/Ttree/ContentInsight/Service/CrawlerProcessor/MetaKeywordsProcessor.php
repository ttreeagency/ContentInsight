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
class MetaKeywordsProcessor implements ProcessorInterface {

	/**
	 * @param string $uri
	 * @param DomCrawler $content
	 * @return string
	 */
	public function process($uri, DomCrawler $content) {
		$keywords = NULL;
		try {
			$keywords = $content->filterXPath('html/head/meta[@name="meta_keywords"]/@content')->text();
		} catch (\InvalidArgumentException $exception) {

		}

		return $keywords;
	}


}