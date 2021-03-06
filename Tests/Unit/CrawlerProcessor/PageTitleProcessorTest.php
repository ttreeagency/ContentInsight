<?php
namespace Ttree\ContentInsight\Tests\Unit\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\CrawlerProcessor\PageTitleProcessor;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PageTitleProcessor
 */
class PageTitleProcessorTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function processReturnThePageTitleTagContent() {
		$this->markTestSkipped('Todo, rewrite based on HTML fixtures');
		$processor = new PageTitleProcessor();

		$uri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();

		$uriDefinition = $this->getMockBuilder('Ttree\ContentInsight\Domain\Model\UriDefinition')->disableOriginalConstructor()->getMock();
		$uriDefinition->expects($this->at(0))->method('getProperty')->with('externalLink')->willReturn(FALSE);
		$uriDefinition->expects($this->at(1))->method('getProperty')->with('depth')->willReturn(0);

		$crawler = $this->getMockBuilder('Ttree\ContentInsight\Service\Crawler')->getMock();
		$crawler->expects($this->once())->method('getProcessedUri')->willReturn($uriDefinition);

		$content = $this->getMockBuilder('Ttree\ContentInsight\Domain\Model\HtmlDocument')->getMock();
		$contentCrawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

		$content->expects($this->once())->method('getCrawler')->willReturn($contentCrawler);

		$contentCrawler->expects($this->once())->method('filterXPath')->with('html/head/title')->willReturn($contentCrawler);
		$contentCrawler->expects($this->once())->method('text')->willReturn('Page Title');

		$this->assertSame('Page Title', $processor->process($uri, $content, $crawler));
	}

	/**
	 * @test
	 */
	public function processReturnThePageTitleTagContentPaddedBasedOnPageDepth() {
		$this->markTestSkipped('Todo, rewrite based on HTML fixtures');
		$processor = new PageTitleProcessor();

		$uri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();

		$uriDefinition = $this->getMockBuilder('Ttree\ContentInsight\Domain\Model\UriDefinition')->disableOriginalConstructor()->getMock();
		$uriDefinition->expects($this->at(0))->method('getProperty')->with('externalLink')->willReturn(FALSE);
		$uriDefinition->expects($this->at(1))->method('getProperty')->with('depth')->willReturn(2);

		$crawler = $this->getMockBuilder('Ttree\ContentInsight\Service\Crawler')->getMock();
		$crawler->expects($this->once())->method('getProcessedUri')->willReturn($uriDefinition);

		$content = $this->getMockBuilder('Ttree\ContentInsight\Domain\Model\HtmlDocument')->getMock();
		$contentCrawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

		$content->expects($this->once())->method('getCrawler')->willReturn($contentCrawler);

		$contentCrawler->expects($this->once())->method('filterXPath')->with('html/head/title')->willReturn($contentCrawler);
		$contentCrawler->expects($this->once())->method('text')->willReturn('Page Title');

		$this->assertSame("\t\tPage Title", $processor->process($uri, $content, $crawler));
	}

}
