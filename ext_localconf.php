<?php

if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

/*
 *
 * XCLASS Extends the tslib_content_Image class which is responsible for rendering IMAGE objects
 *
 */
if(version_compare(TYPO3_version, '5.0.0', '<')) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['tslib/content/class.tslib_content_image.php']
        = t3lib_extMgm::extPath('rtp_imgquery') . 'Classes/Xclass/class.ux_tslib_content_image.php';

} else {
    // new XCLASS mapping that is used by TYPO3 6.0+
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ImageContentObject'] = array(
        'className' => 'RTP\\RtpImgquery\\Xclass\\ImageContentObject'
    );
}

