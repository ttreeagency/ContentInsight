<?php
namespace Ttree\ContentInsight\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;

/**
 * Website Crawler
 */
class PresetDefinition {

	protected $preset = array();

	/**
	 * @param array $preset
	 */
	public function __construct(array $preset) {
		$this->preset = $preset;
	}

	/**
	 * @return array<ReportConfigurationDefinition>
	 */
	public function getReportConfigurations() {
		static $reportConfigurations = NULL;
		if (is_array($reportConfigurations)) {
			return $reportConfigurations;
		}
		foreach (Arrays::getValueByPath($this->preset, 'reportConfigurations') ?: array() as $reportConfiguration) {
			$reportConfigurations[] = new ReportConfigurationDefinition($reportConfiguration);
		}
		return $reportConfigurations;
	}

	/**
	 * @return array
	 */
	public function getPropertyNames() {
		return array_keys($this->getProperties());
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		return Arrays::getValueByPath($this->preset, 'properties') ?: array();
	}

	/**
	 * @return array
	 */
	public function getInvalidUriPatterns() {
		return Arrays::getValueByPath($this->preset, 'invalidUriPatterns') ?: array();
	}
}