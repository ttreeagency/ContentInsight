<?php
namespace Ttree\ContentInsight\ReportBuilder\PropertyPostProcessor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Post Processor Interface
 */
interface PostProcessorInterface {

	/**
	 * @param mixed $propertyValue
	 * @return string
	 */
	public function process($propertyValue);

}