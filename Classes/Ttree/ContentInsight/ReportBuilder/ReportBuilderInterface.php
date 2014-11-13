<?php
namespace Ttree\ContentInsight\ReportBuilder;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Domain\Model\ReportConfigurationDefinition;
use TYPO3\Flow\Annotations as Flow;

/**
 * Report Builder Interface
 */
interface ReportBuilderInterface {

	/**
	 * @param array $inventory
	 * @param ReportConfigurationDefinition $reportConfiguration
	 * @return void
	 */
	public function build(array $inventory, ReportConfigurationDefinition $reportConfiguration);

}