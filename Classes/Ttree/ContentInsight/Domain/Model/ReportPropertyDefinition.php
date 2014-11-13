<?php
namespace Ttree\ContentInsight\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\ReportBuilder\PropertyPostProcessor\PostProcessorInterface;
use Ttree\ContentInsight\ReportBuilder\ReportBuilderInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManager;
use TYPO3\Flow\Utility\Arrays;

/**
 * Report Property Definition
 */
class ReportPropertyDefinition {

	/**
	 * @Flow\Inject
	 * @var ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @param array $configuration
	 */
	public function __construct(array $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * @return mixed
	 */
	public function getLabel() {
		return (string) Arrays::getValueByPath($this->configuration, 'label');
	}

	/**
	 * @return PostProcessorInterface
	 */
	public function getPostProcessor() {
		$postProcessor = Arrays::getValueByPath($this->configuration, 'postProcessor');
		if ($postProcessor === NULL) {
			return NULL;
		}
		if (strpos($postProcessor, "\\") === FALSE) {
			$postProcessor = sprintf('Ttree\ContentInsight\ReportBuilder\PropertyPostProcessor\%sPostProcessor', $postProcessor);
		}

		return $this->objectManager->get($postProcessor);
	}

}