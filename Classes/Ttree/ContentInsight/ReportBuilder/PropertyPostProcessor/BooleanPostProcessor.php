<?php
namespace Ttree\ContentInsight\ReportBuilder\PropertyPostProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Domain\Model\ReportConfigurationDefinition;
use Ttree\ContentInsight\Domain\Model\UriDefinition;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Now;

/**
 * Boolean Property Post Processor
 */
class BooleanPostProcessor implements PostProcessorInterface {

	/**
	 * @param boolean $propertyValue
	 * @return string
	 */
	public function process($propertyValue) {
		return $propertyValue ? 'Yes' : 'No';
	}

}