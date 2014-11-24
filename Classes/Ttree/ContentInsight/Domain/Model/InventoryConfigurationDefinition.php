<?php
namespace Ttree\ContentInsight\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;

/**
 * Inventory Configuration Definition
 */
class InventoryConfigurationDefinition {

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
	 * @param string|array $path
	 * @return bool
	 */
	public function getConfigurationByPath($path) {
		return Arrays::getValueByPath($this->configuration, $path);
	}

	/**
	 * @return boolean
	 */
	public function skipExternalUris() {
		return Arrays::getValueByPath($this->configuration, 'skipExternalUris') ? TRUE : FALSE;
	}
}