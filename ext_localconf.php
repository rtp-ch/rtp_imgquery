<?php

    if (!defined ('TYPO3_MODE')) die ('Access denied.');


	/*
	 *
	 * Extends the tslib_content_Image class which is responsible for rendering IMAGE objects
	 *
	 */

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ImageContentObject'] = array(
		'className' => 'Rtp\\RtpImgquery\\Xclass\\ImageContentObject'
	);
