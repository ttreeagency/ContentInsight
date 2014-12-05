<?php
namespace Ttree\ContentInsight\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ContentInsight".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Response;

/**
 * HTTP Response Utility
 *
 * @Flow\Scope("singleton")
 */
class ResponseUtility {

	/**
	 * Return true if the current response is a redirect
	 *
	 * @param Response $response
	 * @return boolean
	 */
	public static function isRedirect(Response $response) {
		$statusCode = $response->getStatusCode();
		return $statusCode >= 300 && $statusCode < 400;
	}

	/**
	 * Return true if the current response is unsuccessful (non 20x status code)
	 *
	 * @param Response $response
	 * @return boolean
	 */
	public static function isSuccessful(Response $response) {
		$statusCode = $response->getStatusCode();
		return $statusCode >= 200 && $statusCode < 300;
	}

}