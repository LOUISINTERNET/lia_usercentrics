<?php

defined('TYPO3') or die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile('lia_usercentrics', 'Configuration/TypoScript', 'LIA Usercentrics');
