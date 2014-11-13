<?php
namespace Ttree\ContentInsight\ReportBuilder;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Domain\Model\ReportConfigurationDefinition;
use Ttree\ContentInsight\Domain\Model\ReportPropertyDefinition;
use Ttree\ContentInsight\Domain\Model\UriDefinition;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Now;

/**
 * CSV ReportBuilder
 *
 * @Flow\Scope("singleton")
 */
class CsvReportBuilder implements ReportBuilderInterface {

	/**
	 * @param array $inventory
	 * @param ReportConfigurationDefinition $reportConfiguration
	 * @return void
	 */
	public function build(array $inventory, ReportConfigurationDefinition $reportConfiguration) {
		$reportPathAndFilename = $this->getReportPathAndFilename($reportConfiguration);
		$report = fopen($reportPathAndFilename, 'w');
		$properties = $reportConfiguration->getProperties();
		if ($reportConfiguration->getConfigurationByPath('renderTypeOptions.displayColumnHeaders') === TRUE) {
			fputcsv($report, $this->renderHeaderRow($properties));
		}
		foreach ($inventory as $inventoryRow) {
			$row = array();
			/** @var UriDefinition $inventoryRow */
			foreach ($properties as $propertyName => $propertyConfiguration) {
				/** @var ReportPropertyDefinition $propertyConfiguration */
				$propertyValue = $inventoryRow->getProperty($propertyName);

				$postProcessor = $propertyConfiguration->getPostProcessor();
				if ($postProcessor !== NULL) {
					$row[$propertyName] = $postProcessor->process($propertyValue);
				} else {
					$row[$propertyName] = $propertyValue;
				}
			}
			fputcsv($report, $row);
		}

		fclose($report);
	}

	/**
	 * @param array $properties
	 * @return array
	 */
	protected function renderHeaderRow(array $properties) {
		$headerRow = array();
		foreach ($properties as $propertyName => $propertyConfiguration) {
			/** @var ReportPropertyDefinition $propertyConfiguration */
			if (!$propertyConfiguration->getLabel()) {
				throw new \InvalidArgumentException(sprintf('Missing label for property "%s"', $propertyName), 1415888988);
			}
			$headerRow[$propertyName] = $propertyConfiguration->getLabel();
		}
		return $headerRow;
	}

	/**
	 * @param ReportConfigurationDefinition $reportConfiguration
	 * @return string
	 */
	protected function getReportPathAndFilename(ReportConfigurationDefinition $reportConfiguration) {
		$now = new Now();
		$reportDate = $now->format('d-m-Y-H-i');
		return $reportConfiguration->getReportPathAndFilename($reportDate);
	}

}