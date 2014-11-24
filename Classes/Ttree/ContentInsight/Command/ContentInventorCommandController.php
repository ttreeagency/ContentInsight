<?php
namespace Ttree\ContentInsight\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Service\Crawler;
use Ttree\ContentInsight\Service\ReportBuilder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Http\Uri;

/**
 * Extract basic content inventory
 */
class ContentInventorCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var Crawler
	 */
	protected $crawlerService;

	/**
	 * @Flow\Inject
	 * @var ReportBuilder
	 */
	protected $reportBuilder;

	/**
	 *  Extract basic content inventory from the give base URL
	 *
	 * @param string $baseUrl
	 * @param string $preset
	 */
	public function extractCommand($baseUrl, $preset = 'default') {
		$this->outputLine();
		$this->outputLine('Content Inventory extraction tools ...');
		try {
			$uri = new Uri($baseUrl);
			$this->outputLine(sprintf('Extract content from "%s%s"', $uri->getHost(), $uri->getPath()));
			$inventory = $this->crawlerService
				->setPreset($preset)
				->crawleFromBaseUri($baseUrl);
			$this->reportBuilder->build($inventory, $this->crawlerService->getCurrentPreset());
			$this->outputLine('Page count: %d', array(count($inventory)));
		} catch (\Exception $exception) {
			$this->outputLine();
			$this->outputLine('Something break ...');
			$this->outputLine('-> Exception: ' . get_class($exception));
			$this->outputLine('-> Message: ' . $exception->getMessage());
			$this->sendAndExit(1);
		}
	}

}