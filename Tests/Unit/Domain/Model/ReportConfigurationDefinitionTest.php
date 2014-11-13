<?php
namespace Ttree\ContentInsight\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Domain\Model\ReportConfigurationDefinition;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PageTitleProcessor
 */
class ReportConfigurationDefinitionTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function getReportPathReturnReportPath() {
		$reportConfiguration = new ReportConfigurationDefinition(array(
			'reportPath' => '/tmp'
		));
		$this->assertSame('/tmp', $reportConfiguration->getReportPath());
	}

	/**
	 * @test
	 */
	public function getReportBuilderReturnReportBuilderInstance() {
		$reportConfiguration = new ReportConfigurationDefinition(array(
			'renderType' => 'Csv'
		));
		$builder = $this->getMockBuilder('Ttree\ContentInsight\ReportBuilder\CsvReportBuilder')->getMock();
		$objectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManager')->disableOriginalConstructor()->getMock();
		$objectManager->expects($this->once())->method('get')->with('Ttree\ContentInsight\ReportBuilder\CsvReportBuilder')->willReturn($builder);
		ObjectAccess::setProperty($reportConfiguration, 'objectManager', $objectManager, TRUE);

		$this->assertSame($builder, $reportConfiguration->getReportBuilder());
	}

	/**
	 * @test
	 */
	public function getReportPrefixReturnReportPrefix() {
		$reportConfiguration = new ReportConfigurationDefinition(array(
			'reportPrefix' => 'report-prefix'
		));
		$this->assertSame('report-prefix', $reportConfiguration->getReportPrefix());
	}

	/**
	 * @test
	 */
	public function getReportPrefixReturnReportPrefixDefaultValue() {
		$reportConfiguration = new ReportConfigurationDefinition(array());
		$this->assertSame('report', $reportConfiguration->getReportPrefix());
	}

	/**
	 * @test
	 */
	public function getReportPathAndFilenameReturnTheCompletPath() {
		$reportConfiguration = new ReportConfigurationDefinition(array(
			'reportPath' => '/tmp'
		));
		$this->assertSame('/tmp/report-hello.csv', $reportConfiguration->getReportPathAndFilename('hello'));
	}

	/**
	 * @test
	 */
	public function isEnabledReturnTrueIfTheReportIsEnabled() {
		$reportConfiguration = new ReportConfigurationDefinition(array(
			'enabled' => TRUE
		));
		$this->assertSame(TRUE, $reportConfiguration->isEnabled());
	}



	/**
	 * @test
	 */
	public function isEnabledReturnFalseIfTheReportIsNotEnabled() {
		$reportConfiguration = new ReportConfigurationDefinition(array());
		$this->assertSame(FALSE, $reportConfiguration->isEnabled());

		$reportConfiguration = new ReportConfigurationDefinition(array(
			'enabled' => FALSE
		));
		$this->assertSame(FALSE, $reportConfiguration->isEnabled());
	}

	/**
	 * @test
	 */
	public function getReportConfigurationsReturnArrayOfReportConfiguration() {
		$definition = new ReportConfigurationDefinition(array(
			'properties' => array(
				'foo' => array(),
				'fii' => array(),
			)
		));
		foreach ($definition->getProperties() as $definition) {
			$this->assertInstanceOf('Ttree\ContentInsight\Domain\Model\ReportPropertyDefinition', $definition);
		}
	}

	/**
	 * @test
	 */
	public function getPropertyByPathReturnSpecificPropertyConfiguration() {
		$definition = new ReportConfigurationDefinition(array(
			'properties' => array(
				'foo' => array('foo' => 'foo'),
				'fii' => array('fii' => 'fii'),
			)
		));
		$this->assertSame(array('foo' => 'foo'), $definition->getConfigurationByPath('properties.foo'));
	}

}
