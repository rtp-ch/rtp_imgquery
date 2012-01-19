<?php

    if (!defined('TYPO3_MODE')) die ('Access denied.');
    // No TypoScript to add!
    t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Responsive Images');

    // Language labels
    $breakpointLangLabel = 'LLL:EXT:rtp_imgquery/Resources/Private/Language/locallang_db.xml:tt_content.tx_rtpresponsive_breakpoint';
    $breakpointLangLabels = 'LLL:EXT:rtp_imgquery/Resources/Private/Language/locallang_db.xml:tt_content.tx_rtpresponsive_breakpoints';
    $paletteLangLabels = 'LLL:EXT:rtp_imgquery/Resources/Private/Language/locallang_db.xml:palette.breakpoints';

    // TCA Columns
    $tempColumns = array (
        'tx_rtpresponsive_breakpoint' => array (
            'exclude' => 1,
            'label' => $breakpointLangLabel,
            'config' => array (
                'type' => 'input',
                'size' => '30',
                'eval' => 'int',
            )
        ),
        'tx_rtpresponsive_breakpoints' => array (
            'exclude' => 1,
            'label' => $breakpointLangLabels,
            'config' => array (
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            )
        ),
    );
    t3lib_div::loadTCA('tt_content');
    t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);

    // Creates a new palette "breakpoints"
    $paletteFields  = 'tx_rtpresponsive_breakpoint;' . $breakpointLangLabel . ', --linebreak--, tx_rtpresponsive_breakpoints;' . $breakpointLangLabels . '';
    t3lib_extMgm::addFieldsToPalette('tt_content', 'breakpoints', $paletteFields);

    // But can't seem to insert it after the palette image_accessibility...?
    $insertFields   = '--palette--;' . $paletteLangLabels . ';breakpoints';
    $insertTypes    = 'image,textpic';
    $insertPosition = 'after:palette.image_accessibility;image_accessibility,';
    t3lib_extMgm::addToAllTCAtypes('tt_content', $insertFields, $insertTypes, $insertPosition);