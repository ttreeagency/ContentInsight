<?php
namespace Ttree\ContentInsight\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Domain\Model\PresetDefinition;
use Ttree\ContentInsight\Domain\Model\ReportConfigurationDefinition;
use TYPO3\Flow\Annotations as Flow;

/**
 * Report Builder
 *
 * @Flow\Scope("singleton")
 */
class ReportBuilder {

	/**
	 * @param array $inventory
	 * @param PresetDefinition $preset
	 */
	public function build(array $inventory, PresetDefinition $preset) {
		foreach ($preset->getReportConfigurations() as $reportConfiguration) {
			/** @var ReportConfigurationDefinition $reportConfiguration */
			if (!$reportConfiguration->isEnabled()) {
				continue;
			}
			$builder = $reportConfiguration->getReportBuilder();
			$builder->build($inventory, $reportConfiguration);
		}
	}

}