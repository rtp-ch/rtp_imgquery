<?php

    if (!defined ('TYPO3_MODE')) die ('Access denied.');


    /*
     *
     * XCLASS Extends the tslib_content_Image class which is responsible for rendering IMAGE objects
     *
     */

    // TODO: Check versions for availability of "tslib_content_Image" --> if(version_compare(TYPO3_version, '4.3.0', '>'))
    $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_image.php'] = t3lib_extMgm::extPath('rtp_imgquery') . 'Classes/Xclass/class.ux_tslib_content_image.php';