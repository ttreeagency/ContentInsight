<?php
namespace Ttree\ContentInsight\Tests\Unit\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\CrawlerProcessor\MetaDescriptionProcessor;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PageTitleProcessor
 */
class MetaDescriptionProcessorTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function processReturnPageMetaDescriptionValue() {
		$processor = new MetaDescriptionProcessor();

		$uri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();

		$crawler = $this->getMockBuilder('Ttree\ContentInsight\Service\Crawler')->getMock();

		$content = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
		$content->expects($this->once())->method('filterXPath')->with('html/head/meta[@name="description"]/@content')->willReturn($content);
		$content->expects($this->once())->method('text')->willReturn('Page Meta Description');

		$this->assertSame('Page Meta Description', $processor->process($uri, $content, $crawler));
	}

}
