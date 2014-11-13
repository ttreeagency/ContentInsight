<?php
namespace Ttree\ContentInsight\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Service\Crawler;
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
	 *  Extract basic content inventory from the give base URL
	 *
	 * @param string $baseUrl
	 */
	public function extractCommand($baseUrl) {
		$this->outputLine();
		$this->outputLine('Content Inventory extraction tools ...');
		try {
			$uri = new Uri(trim($baseUrl, '/'));
			$this->outputLine(sprintf('Extract content from "%s%s"', $uri->getHost(), $uri->getPath()));
			$inventory = $this->crawlerService
				->setPreset('default')
				->crawleFromBaseUri($baseUrl);
			var_dump($inventory);
			$this->outputLine('Page count: %d', array(count($inventory)));
		} catch (\InvalidArgumentException $exception) {
			$this->outputLine('Something break ...');
			$this->outputLine(get_class($exception));
			$this->outputLine($exception->getCode());
			$this->outputLine($exception->getMessage());
			$this->sendAndExit(1);
		}
	}

}