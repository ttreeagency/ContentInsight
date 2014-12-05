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
class FirstLevelHeaderProcessor implements ProcessorInterface {

	/**
	 * @param string $uri
	 * @param HtmlDocument $document
	 * @param Crawler $crawler
	 * @return array
	 */
	public function process($uri, HtmlDocument $document, Crawler $crawler) {
		$result = array();
		$headers = $document->getCrawler()->filterXPath('descendant-or-self::h1');
		$firstLevelHeaderCounter = $headers->count();
		$result['firstLevelHeaderCount'] = $firstLevelHeaderCounter;
		if ($firstLevelHeaderCounter > 0) {
			$contents = array();
			$headers->each(function (DomCrawler $node, $i) use (&$contents) {
				try {
					$contents[$i] = trim($node->text());
				} catch (\InvalidArgumentException $exception) {
					$contents[$i] = NULL;
				}
			});
			$result['firstLevelHeaderContent'] = implode($contents, '; ');
		}

		return $result;
	}


}