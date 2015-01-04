<?php
namespace Ttree\ContentInsight\Tests\Unit\CrawlerProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\CrawlerProcessor\FirstLevelHeaderProcessor;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PageTitleProcessor
 */
class FirstLevelHeaderProcessorTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function processReturnPageMetaDescriptionValue() {
		$processor = new FirstLevelHeaderProcessor();

		$uri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();

		$crawler = $this->getMockBuilder('Ttree\ContentInsight\Service\Crawler')->getMock();

		$content = $this->getMockBuilder('Ttree\ContentInsight\Domain\Model\HtmlDocument')->getMock();
		$contentCrawler = $this->getMockBuilder('Symfony\Component\DomCrawler\Crawler')->getMock();

		$content->expects($this->once())->method('getCrawler')->willReturn($contentCrawler);
		$contentCrawler->expects($this->once())->method('count')->willReturn(2);

		$contentCrawler->expects($this->once())->method('filterXPath')->with('descendant-or-self::h1')->willReturn($contentCrawler);

		$this->assertSame(array(
			'firstLevelHeaderCount' => 2,
			'firstLevelHeaderContent' => ''
		), $processor->process($uri, $content, $crawler));
	}

}
