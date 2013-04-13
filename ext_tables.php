<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// No TypoScript to add!
t3lib_extMgm::addStaticFile('rtp_imgquery', 'Configuration/TypoScript/', 'Responsive Images');

// Language labels
$langFile = 'LLL:EXT:rtp_imgquery/Resources/Private/Language/locallang_db.xml:';
$breakpointLangLabel = $langFile . 'tt_content.tx_rtpimgquery_breakpoint';
$breakpointLangLabels = $langFile . 'tt_content.tx_rtpimgquery_breakpoints';
$pixelRatioLangLabels = $langFile . 'tt_content.tx_rtpimgquery_pixel_ratios';
$paletteLangLabels = $langFile . 'palette.breakpoints';

// TCA Columns
$tempColumns = array (
    'tx_rtpimgquery_breakpoint' => array (
        'exclude' => 1,
        'label' => $breakpointLangLabel,
        'config' => array (
            'type' => 'input',
            'size' => '12',
            'eval' => 'int',
        )
    ),
    'tx_rtpimgquery_breakpoints' => array (
        'exclude' => 1,
        'label' => $breakpointLangLabels,
        'config' => array (
            'type' => 'text',
            'cols' => '30',
            'rows' => '5',
        )
    ),
    'tx_rtpimgquery_pixel_ratios' => array (
        'exclude' => 1,
        'label' => $pixelRatioLangLabels,
        'config' => array (
            'type' => 'input',
            'size' => '30',
            'eval' => 'int',
        )
    ),
);
t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);

// Creates a new palette "breakpoints"
$paletteFields  = 'tx_rtpimgquery_breakpoint;' . $breakpointLangLabel;
$paletteFields .= ', --linebreak--, tx_rtpimgquery_breakpoints;' . $breakpointLangLabels;
$paletteFields .= ', --linebreak--, tx_rtpimgquery_pixel_ratios;' . $pixelRatioLangLabels;
t3lib_extMgm::addFieldsToPalette('tt_content', 'breakpoints', $paletteFields);

// TODO: Insert the new palette after the palette image_accessibility
$insertFields   = '--palette--;' . $paletteLangLabels . ';breakpoints';
$insertTypes    = 'image,textpic';
// This doesn't seem to work...
$insertPosition = 'after:palette.image_accessibility;image_accessibility,';
t3lib_extMgm::addToAllTCAtypes('tt_content', $insertFields, $insertTypes, $insertPosition);