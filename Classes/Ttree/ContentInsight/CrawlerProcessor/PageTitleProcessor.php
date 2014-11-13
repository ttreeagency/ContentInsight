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
class PageTitleProcessor implements ProcessorInterface {

	/**
	 * @param string $uri
	 * @param DomCrawler $content
	 * @param Crawler $crawler
	 * @return string
	 */
	public function process($uri, DomCrawler $content, Crawler $crawler) {
		$title = NULL;
		try {
			$title = $content->filterXPath('html/head/title')->text();
			$uriDefinition = $crawler->getProcessedUri($uri);
			if (!$uriDefinition->getProperty('external_link')) {
				$depth = $uriDefinition->getProperty('depth') - 1 ?: 0;
				if ($depth > 0) {
					$prefix = str_pad('', $depth, "\t", STR_PAD_LEFT);
					$title = sprintf('%s %s', $prefix, $title);
				}
			}
		} catch (\InvalidArgumentException $exception) {

		}

		return $title;
	}


}