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
	 * @param string $charset
	 *
	 * @api
	 */
	public function __construct($content = null, $charset = 'UTF-8') {

		$this->document = new \DOMDocument('1.0', $charset);
		$this->document->validateOnParse = true;

		if (function_exists('mb_convert_encoding') && in_array(strtolower($charset), array_map('strtolower', mb_list_encodings()))) {
			$content = mb_convert_encoding($content, 'HTML-ENTITIES', $charset);
		}

		@$this->document->loadHTML($content);
		$this->document->formatOutput = true;

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