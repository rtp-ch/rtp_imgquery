<?php

    if (!defined('TYPO3_MODE')) die ('Access denied.');
    // No TypoScript to add!
    // t3lib_extMgm::addStaticFile($_EXTKEY,'static/imgquery/', 'Responsive Images');

    $tempColumns = array (
        'tx_rtpresponsive_breakpoint' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:rtp_responsive/Resources/Private/Language/locallang_db.xml:tt_content.tx_rtpresponsive_breakpoint',
            'config' => array (
                'type' => 'input',
                'size' => '30',
                'eval' => 'int',
            )
        ),
        'tx_rtpresponsive_breakpoints' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:rtp_responsive/Resources/Private/Language/locallang_db.xml:tt_content.tx_rtpresponsive_breakpoints',
            'config' => array (
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            )
        ),
    );


    t3lib_div::loadTCA('tt_content');
    t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);
    t3lib_extMgm::addToAllTCAtypes('tt_content', 'tx_rtpresponsive_breakpoint;;;;1-1-1, tx_rtpresponsive_breakpoints');
    //t3lib_extMgm::addFieldsToPalette('tt_content', 'breakpoints', 'tx_rtpresponsive_breakpoint, tx_rtpresponsive_breakpoints');