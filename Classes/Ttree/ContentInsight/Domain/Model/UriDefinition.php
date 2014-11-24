<?php
namespace Ttree\ContentInsight\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Utility\Arrays;

/**
 * Uri Definition
 */
class UriDefinition {

	/**
	 * @var Uri
	 */
	protected $uri;

	protected $properties = array();

	/**
	 * @param Uri $uri
	 * @param array $properties
	 */
	public function __construct(Uri $uri, array $properties = array()) {
		$this->uri = $uri;
		$this->properties = $properties;
	}

	/**
	 * @return string
	 */
	function __toString() {
		return (string)$this->uri;
	}


	/**
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param array $properties
	 * @return $this
	 */
	public function setProperties(array $properties) {
		foreach ($properties as $propertyName => $propertyValue) {
			$this->setProperty($propertyName, $propertyValue);
		}
		return $this;
	}

	/**
	 * @param string $propertyName
	 * @param mixed $propertyValue
	 * @return $this
	 */
	public function setProperty($propertyName, $propertyValue) {
		$this->properties[$propertyName] = $propertyValue;
		return $this;
	}

	/**
	 * @param string $propertyName
	 * @return mixed
	 */
	public function getProperty($propertyName) {
		return Arrays::getValueByPath($this->properties, $propertyName);
	}

	/**
	 * @return $this
	 */
	public function incrementFrequency() {
		$frequency = $this->getProperty('frequency');
		if ($frequency === NULL) {
			$this->setProperty('frequency', 1);
		} else {
			$this->setProperty('frequency', ++$frequency);
		}
		return $this;
	}

	/**
	 * @return Uri
	 */
	public function getUri() {
		return $this->uri;
	}
}