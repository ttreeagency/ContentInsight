<?php
namespace Ttree\ContentInsight\Tests\Unit\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\CrawlerProcessor\NavigationTitleProcessor;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PageTitleProcessor
 */
class NavigationTitleProcessorTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function processReturnThePageTitleTagContent() {
		$processor = new NavigationTitleProcessor();
		$this->markTestIncomplete("Implementation currently not finished");

		$uri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();

		$uriDefinition = $this->getMockBuilder('Ttree\ContentInsight\Domain\Model\UriDefinition')->disableOriginalConstructor()->getMock();
		$uriDefinition->expects($this->at(0))->method('getProperty')->with('external_link')->willReturn(FALSE);
		$uriDefinition->expects($this->at(1))->method('getProperty')->with('depth')->willReturn(0);

		$crawler = $this->getMockBuilder('Ttree\ContentInsight\Service\Crawler')->getMock();
		$crawler->expects($this->once())->method('getProcessedUri')->willReturn($uriDefinition);

		$content = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();
		$content->expects($this->once())->method('filterXPath')->with('html/head/title')->willReturn($content);
		$content->expects($this->once())->method('text')->willReturn('Page Title');

		$this->assertSame('Home', $processor->process($uri, $content, $crawler));
	}

}
