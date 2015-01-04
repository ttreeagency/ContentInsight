<?php
namespace Ttree\ContentInsight\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Symfony\Component\DomCrawler\Crawler;
use TYPO3\Flow\Annotations as Flow;

/**
 * Html Document
 */
class HtmlDocument {

	/**
	 * @var \DOMDocument
	 */
	protected $document;

	/**
	 * @var Crawler
	 */
	protected $crawler;

	/**
	 * Constructor.
	 *
	 * @param mixed $content A Node to use as the base for the crawling
	 * @param string $encoding
	 *
	 * @api
	 */
	public function __construct($content = NULL, $encoding = NULL) {

		if ($encoding === NULL) {
			$encoding = mb_detect_encoding($content);
		}

		if ($encoding === FALSE || $encoding === NULL) {
			throw new \InvalidArgumentException('Please provide a valid encoding for the current document, unable to detect the current encoding', 1417823522);
		}

		if ($encoding !== 'UTF-8') {
			$content = mb_convert_encoding($content, 'UTF-8', $encoding);
		}

		$this->document = new \DOMDocument('1.0', $encoding);
		$this->document->validateOnParse = TRUE;

		@$this->document->loadHTML($content);

		$this->crawler = new Crawler($content);
	}

	/**
	 * Get the page title of the HTML document
	 *
	 * @return null|string
	 */
	public function getTitle() {
		$title = $this->document->getElementsByTagName('title')->item(0);
		return $title ? $title->nodeValue : NULL;
	}

	/**
	 * @return Crawler
	 */
	public function getCrawler() {
		return $this->crawler;
	}

}