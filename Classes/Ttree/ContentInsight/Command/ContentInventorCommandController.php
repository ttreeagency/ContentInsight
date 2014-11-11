<?php
namespace Ttree\ContentInsight\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use Ttree\ContentInsight\Service\CrawlerService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Http\Uri;

/**
 * Extract basic content inventory
 */
class ContentInventorCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var CrawlerService
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
			$inventoryReport = $this->crawlerService->processFromBaseUri($baseUrl);
			var_dump($inventoryReport);
			$this->outputLine('Page count: %d', array(count($inventoryReport)));
		} catch (\InvalidArgumentException $exception) {
			$this->outputLine('Something break ...');
			$this->outputLine(get_class($exception));
			$this->outputLine($exception->getCode());
			$this->outputLine($exception->getMessage());
			$this->sendAndExit(1);
		}
	}

}