<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
    \Macopedia\MessDetector\Command\DatabaseIntegrityCommandController::class;

