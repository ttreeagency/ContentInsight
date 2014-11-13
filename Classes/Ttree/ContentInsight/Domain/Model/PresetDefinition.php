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
	 * @return array
	 */
	public function getProperties() {
		return Arrays::getValueByPath($this->preset, 'properties') ?: array();
	}
}