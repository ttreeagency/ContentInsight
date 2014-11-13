<?php
namespace Ttree\ContentInsight\Tests\Unit\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\CrawlerProcessor\FirstLevelHeaderProcessor;
use Ttree\ContentInsight\Domain\Model\PresetDefinition;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the PageTitleProcessor
 */
class PresetDefinitionTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function getReportConfigurationsReturnArrayOfReportConfiguration() {
		$preset = new PresetDefinition(array(
			'reportConfigurations' => array(
				'foo' => array(
					'foo' => 'fii'
				)
			)
		));
		foreach ($preset->getReportConfigurations() as $reportConfiguration) {
			$this->assertInstanceOf('Ttree\ContentInsight\Domain\Model\ReportConfigurationDefinition', $reportConfiguration);
		}
	}

	/**
	 * @test
	 */
	public function getReportConfigurationsReturnAnEmptyArrayWhenReportsAreNotConfigured() {
		$preset = new PresetDefinition(array());
		$this->assertSame(array(), $preset->getReportConfigurations());
	}

	/**
	 * @test
	 */
	public function getPropertyNamesReturnAnArrayWithAllPropertyName() {
		$preset = new PresetDefinition(array(
			'properties' => array(
				'foo' => array(
					'foo' => 'fii'
				),
				'fii' => array(
					'foo' => 'fii'
				),
				'bla' => array(
					'foo' => 'fii'
				),
			)
		));
		$this->assertSame(array('foo', 'fii', 'bla'), $preset->getPropertyNames());
	}

	/**
	 * @test
	 */
	public function getPropertiesReturnAllProperties() {
		$properties = array(
			'foo' => array(
				'foo' => 'fii'
			),
			'fii' => array(
				'foo' => 'fii'
			),
			'bla' => array(
				'foo' => 'fii'
			),
		);
		$preset = new PresetDefinition(array(
			'properties' => $properties
		));
		$this->assertSame($properties, $preset->getProperties());
	}

	/**
	 * @test
	 */
	public function getInvalidUriPatternsReturnAllInvalidUriPatterns() {
		$patterns = array(
			'foo' => array(
				'foo' => 'fii'
			),
			'fii' => array(
				'foo' => 'fii'
			),
			'bla' => array(
				'foo' => 'fii'
			),
		);
		$preset = new PresetDefinition(array(
			'invalidUriPatterns' => $patterns
		));
		$this->assertSame($patterns, $preset->getInvalidUriPatterns());
	}

}
