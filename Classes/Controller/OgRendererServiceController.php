<?php
namespace Heilmann\JhOpengraphprotocol\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2014-2015 Jonathan Heilmann <mail@jonathan-heilmann.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
* @author    Jonathan Heilmann <mail@jonathan-heilmann.de>
* @package    tx_jhopengraphprotocol
*/
class OgRendererServiceController extends \TYPO3\CMS\Extensionmanager\Controller\ActionController {

	/**
	 * content Object
	 */
	protected $cObj;

	/**
	 * Main-function to render the Open Grapg protocol content.
	 */
	public function mainAction(){

		$this->cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$extKey = 'tx_jhopengraphprotocol';
		$content = '';
		$og = array();

		// 2013-04-22	kraftb@webconsulting.at
		// Check if the tt_news "displaySingle" method has been called before
		if(class_exists('tx_jhopengraphttnews_displaySingleHook')) {
			$hookObject = GeneralUtility::makeInstance('tx_jhopengraphttnews_displaySingleHook');
			if ($hookObject->singleViewDisplayed()) {
				return $content;
			}
		}

		//if there has been no return, get og properties and render output

		// Get title
		if (!empty($GLOBALS['TSFE']->page['tx_jhopengraphprotocol_ogtitle'])) {
			$og['title'] = $GLOBALS['TSFE']->page['tx_jhopengraphprotocol_ogtitle'];
		} else {
			$og['title'] = $GLOBALS['TSFE']->page['title'];
		}
		$og['title'] = htmlspecialchars($og['title']);

		// Get type
		if (!empty($GLOBALS['TSFE']->page['tx_jhopengraphprotocol_ogtype'])) {
			$og['type'] = $GLOBALS['TSFE']->page['tx_jhopengraphprotocol_ogtype'];
		} else {
			$og['type'] = $this->settings['type'];
		}
		$og['type'] = htmlspecialchars($og['type']);

		// Get image
		$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileObjects = $fileRepository->findByRelation('pages', 'tx_jhopengraphprotocol_ogfalimages', $GLOBALS['TSFE']->id);
		if (count($fileObjects)) {
			foreach ($fileObjects as $key => $fileObject) {
				$og['image'][] = GeneralUtility::locationHeaderUrl($fileObject->getPublicUrl());
			}
		} else {
			// check if an image is given in page --> media, if not use default image
			$fileObjects = $fileRepository->findByRelation('pages', 'media', $GLOBALS['TSFE']->id);
			if (count($fileObjects)) {
				foreach ($fileObjects as $key => $fileObject) {
					$og['image'][] = GeneralUtility::locationHeaderUrl($fileObject->getPublicUrl());
				}
			}
//			else {
//				if (!empty($GLOBALS['TSFE']->tmpl->getFileName($conf['image']))) {
//					$og['image'][] = GeneralUtility::locationHeaderUrl($GLOBALS['TSFE']->tmpl->getFileName($conf['image']));
//				}
//			}
		}

		// Get url
		$og['url'] = $this->addBaseUriIfNecessary($this->uriBuilder->buildFrontendUri());

		// Get site_name
		if (!empty($this->settings['sitename'])) {
			$og['site_name'] = $this->settings['sitename'];
		} else {
			$og['site_name'] = $GLOBALS['TSFE']->TYPO3_CONF_VARS['SYS']['sitename'];
		}
		$og['site_name'] = htmlspecialchars($og['site_name']);

		// Get description
		if (!empty($GLOBALS['TSFE']->page['tx_jhopengraphprotocol_ogdescription'])) {
			$og['description'] = $GLOBALS['TSFE']->page['tx_jhopengraphprotocol_ogdescription'];
		} else {
			if (!empty($GLOBALS['TSFE']->page['description'])) {
				$og['description'] = $GLOBALS['TSFE']->page['description'];
			} else {
				$og['description'] = $this->settings['description'];
			}
		}
		$og['description'] = htmlspecialchars($og['description']);

		// Get locale
		$localeParts = explode('.', $GLOBALS['TSFE']->tmpl->setup['config.']['locale_all']);
		if (isset($localeParts[0])) {
			$og['locale'] = str_replace('-', '_', $localeParts[0]);
		}

		//add tags to html-header
		$GLOBALS['TSFE']->additionalHeaderData[$extKey] = $this->renderHeaderLines($og);

		return $content;
	}

	/**
	 * Render the header lines to be added from array
	 *
	 * @param	array		$array
	 * @return	string
	 */
	private function renderHeaderLines($array) {
		$res = array();
		foreach ($array as $key => $value) {
			if (!empty($value )) { // Skip empty values to prevent from empty og property
				if (is_array($value)) {
					// A op property with multiple values or child-properties
					if(array_key_exists('0', $value)) {
						// A og property that accepts more than one value
						foreach ($value as $multiPropertyValue) {
							// Render each value to a new og property meta-tag
							$res[] = '<meta property="og:'.$key.'" content="'.$multiPropertyValue.'" />';
						}
					} else {
						// A og property with child-properties
						$res .= $this->renderHeaderLines($this->remapArray($key, $value));
					}
				} else {
					// A singe og property to be rendered
					$res[] = '<meta property="og:'.$key.'" content="'.$value.'" />';
				}
			}
		}
		return implode(chr(10), $res);
	}

	/**
	 * Remap an array: Add $prefixKey to keys of $array
	 *
	 * @param	string	$prefixKey
	 * @param	array		$array
	 * @return	array
	 */
	private function remapArray($prefixKey, $array) {
		$res = array();
		foreach ($array as $key => $value) {
			$res[$prefixKey.':'.$key] = $value;
		}

		return $res;
	}
}
?>