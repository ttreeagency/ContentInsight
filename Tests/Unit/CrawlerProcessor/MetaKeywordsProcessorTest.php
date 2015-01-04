<?php
namespace Ttree\ContentInsight\Tests\Unit\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\CrawlerProcessor\MetaDescriptionProcessor;
use Ttree\ContentInsight\CrawlerProcessor\MetaKeywordsProcessor;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PageTitleProcessor
 */
class MetaKeywordsProcessorTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function processReturnPageMetaKeywordsValue() {
		$processor = new MetaKeywordsProcessor();

		$uri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();

		$crawler = $this->getMockBuilder('Ttree\ContentInsight\Service\Crawler')->getMock();

		$content = $this->getMockBuilder('Ttree\ContentInsight\Domain\Model\HtmlDocument')->getMock();
		$contentCrawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

		$content->expects($this->once())->method('getCrawler')->willReturn($contentCrawler);

		$contentCrawler->expects($this->once())->method('filterXPath')->with('html/head/meta[@name="keywords"]/@content')->willReturn($contentCrawler);
		$contentCrawler->expects($this->once())->method('text')->willReturn('Hello World, Happy World');

		$this->assertSame('Hello World, Happy World', $processor->process($uri, $content, $crawler));
	}

}
