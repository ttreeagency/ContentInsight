<?php
namespace Ttree\ContentInsight\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\ReportBuilder\ReportBuilderInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManager;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 * Report Configuration Definition
 */
class ReportConfigurationDefinition {

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
	 * @return ReportBuilderInterface
	 */
	public function getReportBuilder() {
		$reportBuilder = Arrays::getValueByPath($this->configuration, 'renderType');
		if (strpos($reportBuilder, '\\') === FALSE) {
			$reportBuilder = sprintf('Ttree\ContentInsight\ReportBuilder\%sReportBuilder', $reportBuilder);
		}

		return $this->objectManager->get($reportBuilder);
	}

	/**
	 * @return string
	 */
	public function getReportPath() {
		$reportPath = Arrays::getValueByPath($this->configuration, 'reportPath');
		if (trim($reportPath) === '') {
			throw new \InvalidArgumentException('Empty report path or unable to create the directory', 1415749035);
		}
		Files::createDirectoryRecursively($reportPath);

		return $reportPath;
	}

	/**
	 * @return string
	 */
	public function getReportPrefix() {
		return Arrays::getValueByPath($this->configuration, 'reportPrefix') ?: 'report';
	}

	/**
	 * @param string $suffix
	 * @return string
	 */
	public function getReportPathAndFilename($suffix) {
		return sprintf('%s/%s-%s.csv', $this->getReportPath(), $this->getReportPrefix(), $suffix);
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		$properties = array();
		$sorter = new PositionalArraySorter(Arrays::getValueByPath($this->configuration, 'properties') ?: array());
		foreach ($sorter->toArray() as $propertyName => $propertyConfiguration ) {
			$properties[$propertyName] = new ReportPropertyDefinition($propertyConfiguration);
		}

		return $properties;
	}

	/**
	 * @return boolean
	 */
	public function isEnabled() {
		return (boolean)Arrays::getValueByPath($this->configuration, 'enabled');
	}

	/**
	 * @param string|array $path
	 * @return bool
	 */
	public function getConfigurationByPath($path) {
		return Arrays::getValueByPath($this->configuration, $path);
	}
}