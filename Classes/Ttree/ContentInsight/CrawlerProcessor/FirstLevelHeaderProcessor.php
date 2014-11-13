<?php
namespace Ttree\ContentInsight\CrawlerProcessor;

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
class FirstLevelHeaderProcessor implements ProcessorInterface {

	/**
	 * @param string $uri
	 * @param DomCrawler $content
	 * @return array
	 */
	public function process($uri, DomCrawler $content) {
		$result = array();
		$headers = $content->filter('h1');
		$firstLevelHeaderCounter = $headers->count();
		$result['first_level_header_count'] = $firstLevelHeaderCounter;
		if ($firstLevelHeaderCounter > 0) {
			$contents = array();
			$headers->each(function (DomCrawler $node, $i) use (&$contents) {
				$contents[$i] = trim($node->text());
			});
			$result['first_level_header_content'] = implode($contents, '; ');
		}

		return $result;
	}


}