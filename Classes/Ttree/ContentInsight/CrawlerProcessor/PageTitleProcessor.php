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
use TYPO3\Flow\Utility\Unicode\Functions;

/**
 * Website Crawler
 *
 * @Flow\Scope("singleton")
 */
class PageTitleProcessor implements ProcessorInterface {

	/**
	 * @param string $uri
	 * @param HtmlDocument $document
	 * @param Crawler $crawler
	 * @return string
	 */
	public function process($uri, HtmlDocument $document, Crawler $crawler) {
		try {
			$title = trim($document->getTitle());
			$uriDefinition = $crawler->getProcessedUri($uri);
			if (!$uriDefinition->getProperty('externalLink')) {
				$depth = $uriDefinition->getProperty('depth');
				if ($depth > 0) {
					$prefix = '';
					for ($i = 0; $i < $depth; $i++) {
						$prefix .= "\t";
					}
					$title = sprintf('%s%s', $prefix, $title);
				}
			}
		} catch (\InvalidArgumentException $exception) {
			$title = NULL;
		}

		return array(
			'pageTitle' => $title,
			'pageTitleLength' => Functions::strlen($title)
		);
	}


}