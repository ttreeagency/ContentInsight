<?php
namespace Ttree\ContentInsight\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;

/**
 * Uri Definition
 */
class UriDefinition {

	protected $properties = array();

	/**
	 * @param array $properties
	 */
	public function __construct(array $properties = array()) {
		$this->properties = $properties;
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param string $propertyName
	 * @param mixed $propertyValue
	 */
	public function setProperty($propertyName, $propertyValue) {
		$this->properties[$propertyName] = $propertyValue;
	}

	/**
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getProperty($propertyName) {
		return Arrays::getValueByPath($this->properties, $propertyName);
	}
}