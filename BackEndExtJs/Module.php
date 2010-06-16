<?php
/***************************************************************
* Copyright notice
*
* (c) 2006-2010 Mario Rimann (typo3-coding@rimann.org)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once($GLOBALS['BACK_PATH'] . 'template.php');

/**
 * Module 'Events' for the 'seminars' extension (the ExtJS version).
 *
 * @package TYPO3
 * @subpackage tx_seminars
 *
 * @author Mario Rimann <typo3-coding@rimann.org>
 * @author Niels Pardon <mail@niels-pardon.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_seminars_BackEndExtJs_Module extends t3lib_SCbase {
	/**
	 * locallang files to add as inline language labels
	 *
	 * @var array
	 */
	static private $locallangFiles = array(
		'EXT:lang/locallang_common.xml',
		'EXT:lang/locallang_core.xml',
		'EXT:lang/locallang_show_rechis.xml',
		'EXT:lang/locallang_mod_web_list.xml',
		'EXT:seminars/BackEnd/locallang.xml',
		'EXT:seminars/pi2/locallang.xml',
	);

	/**
	 * Initializes some variables and also starts the initialization of the
	 * parent class.
	 */
	public function init() {
		parent::init();

		$this->id = intval($this->id);

		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_strict';

		$this->content = '';
	}

	/**
	 * Main function of the module. Writes the content to $this->content.
	 *
	 * @return string the HTML code generated by this module
	 */
	public function main() {
		$this->getPageRenderer()->addCssFile(
			'../Resources/Public/CSS/BackEndExtJs/BackEnd.css',
			'stylesheet',
			'all',
			'',
			FALSE
		);
		$this->getPageRenderer()->addCssFile(
			'../Resources/Public/CSS/BackEndExtJs/Print.css',
			'stylesheet',
			'print',
			'',
			FALSE
		);

		if (!$this->isPageIdValid()) {
			$message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('message_noPageTypeSelected'),
				'',
				t3lib_FlashMessage::INFO
			);
			$this->content = $message->render();
			return $this->outputContent();
		}

		if (!$this->isAdminOrHasPageShowAccess()) {
			return $this->outputContent();
		}

		if (!$this->hasStaticTemplate()) {
			$message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('message_noStaticTemplateFound'),
				'',
				t3lib_FlashMessage::WARNING
			);
			$this->content = $message->render();
			return $this->outputContent();
		}

		$this->getPageRenderer()->addJsFile(
			'../Resources/Public/JavaScript/BackEndExtJs/BackEnd.js',
			'text/javascript',
			FALSE,
			TRUE
		);
		$this->getPageRenderer()->loadExtJS();
		$this->addInlineLanguageLabels();
		$this->addInlineSettings();

		return $this->outputContent();
	}

	/**
	 * Reads all language labels from every file listed in $this->locallangFiles
	 * and adds them as inline language labels to the page renderer which
	 * outputs them as JSON in the page header.
	 */
	private function addInlineLanguageLabels() {
		$language = $GLOBALS['LANG']->lang;
		$charset = $GLOBALS['LANG']->charSet;

		foreach (self::$locallangFiles as $file) {
			$labelsInAllLanguages
				= t3lib_div::readLLfile($file, $language, $charset);
			$labelsToUse = $labelsInAllLanguages['default'];
			if ($language !== 'default') {
				$labelsToUse = array_replace(
					$labelsToUse, $labelsInAllLanguages[$language]
				);
			}
			$this->getPageRenderer()->addInlineLanguageLabelArray(
				$labelsToUse
			);
		}
	}

	/**
	 * Adds some inline settings to the page renderer which outputs them as
	 * JSON in the page header.
	 */
	private function addInlineSettings() {
		$this->addSubmoduleAccessInlineSettings();

		$this->getPageRenderer()->addInlineSetting(FALSE, 'PID', $this->id);

		$this->getPageRenderer()->addInlineSettingArray(
			'Backend.Seminars.URL',
			array(
				'ajax' => $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=',
				'alt_doc' => $GLOBALS['BACK_PATH'] . 'alt_doc.php',
				'csv' => '../BackEnd/class.tx_seminars_BackEnd_CSV.php',
			)
		);
	}

	/**
	 * Adds the sub-module access settings as inline setting to the page
	 * renderer which outputs them as JSON in the page header.
	 */
	private function addSubmoduleAccessInlineSettings() {
		$tables = array(
			'Events' => 'tx_seminars_seminars',
			'Registrations' => 'tx_seminars_attendances',
			'Speakers' => 'tx_seminars_speakers',
			'Organizers' => 'tx_seminars_organizers',
		);

		foreach ($tables as $module => $table) {
			$select = $GLOBALS['BE_USER']->check('tables_select', $table);
			$modify = $GLOBALS['BE_USER']->check('tables_modify', $table);

			$this->getPageRenderer()->addInlineSettingArray(
				'Backend.Seminars',
				array(
					$module => array(
						'TabPanel' => array(
							'hidden' => !$select,
						),
						'GridPanel' => array(
							'NewButton' => array('hidden' => !$modify),
						),
						'Menu' => array(
							'ConfirmButton' => array('hidden' => !$modify),
							'CancelButton' => array('hidden' => !$modify),
							'HideButton' => array('hidden' => !$modify),
							'UnhideButton' => array('hidden' => !$modify),
							'EditButton' => array('hidden' => !$modify),
							'DeleteButton' => array('hidden' => !$modify),
						),
					)
				)
			);
		}
	}

	/**
	 * Wraps the content in $this->content with the HTML for the page start and
	 * the page end and echos it.
	 */
	private function outputContent() {
		return $this->doc->startPage($GLOBALS['LANG']->getLL('title')) .
			$this->content .
			$this->doc->endPage();
	}

	/**
	 * Checks whether this extension's static template is included on the
	 * current page.
	 *
	 * @return boolean TRUE if the static template has been included, FALSE
	 *                 otherwise
	 */
	protected function hasStaticTemplate() {
		return tx_oelib_ConfigurationRegistry::get('plugin.tx_seminars')
			->getAsBoolean('isStaticTemplateLoaded');
	}

	/**
	 * Returns whether the page UID in $this->id is valid (> 0).
	 *
	 * @return boolean TRUE if the page UID in $this->id is valid, FALSE
	 *                 otherwise
	 */
	protected function isPageIdValid() {
		return ($this->id > 0);
	}

	/**
	 * Returns whether the currently logged in back-end user is an admin user or
	 * has show access to the page with the UID in $this->id.
	 *
	 * @return boolean TRUE if the currently logged in back-end user is an admin
	 *                 user or has page show access, FALSE otherwise
	 */
	protected function isAdminOrHasPageShowAccess() {
		if ($GLOBALS['BE_USER']->user['admin']) {
			return TRUE;
		}

		// t3lib_BEfunc::readPageAccess() returns the page record if the current
		// back-end user has show access, FALSE otherwise
		return is_array(
			t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause)
		);
	}

	/**
	 * Returns the page renderer for this back-end module.
	 *
	 * @return t3lib_PageRenderer the page renderer for this back-end module
	 */
	protected function getPageRenderer() {
		return $this->doc->getPageRenderer();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/BackEndExtJs/Module.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seminars/BackEndExtJs/Module.php']);
}
?>