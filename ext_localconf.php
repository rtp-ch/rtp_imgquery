<?php

if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

/*
 *
 * XCLASS Extends the tslib_content_Image class which is responsible for rendering IMAGE objects
 *
 */
// new XCLASS mapping that is used by TYPO3 6.0+
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ImageContentObject'] = array(
    'className' => 'RTP\\RtpImgquery\\Xclass\\ImageContentObject'
);

