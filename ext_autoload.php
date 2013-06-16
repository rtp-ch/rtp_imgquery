<?php

// loads compatibility functions
require_once __DIR__ . '/Classes/Service/Compatibility.php';

$extensionPath = \RTP\RtpImgquery\Service\Compatibility::extPath('rtp_imgquery');
$extensionClassesPath = $extensionPath . 'Classes/';

return array(
	'tx_rtpimgquery_viewhelpers_imageviewhelper' => $extensionClassesPath . 'ViewHelpers/ImageViewHelper.php',
    'tx_rtpimgquery_client_breakpoints' => $extensionClassesPath . 'Client/Breakpoints.php',
    'tx_rtpimgquery_client_pixelratios' => $extensionClassesPath . 'Client/PixelRatios.php',
    'tx_rtpimgquery_main_image' => $extensionClassesPath . 'Main/Image.php',
    'tx_rtpimgquery_main_width' => $extensionClassesPath . 'Main/Width.php',
    'tx_rtpimgquery_main_height' => $extensionClassesPath . 'Main/Height.php',
    'Tx_RtpImgQuery_Main_Breakpoint' => $extensionClassesPath . 'Main/Breakpoint.php',
    'tx_rtpimgquery_responsive_images' => $extensionClassesPath . 'Responsive/Images.php',
    'tx_rtpimgquery_responsive_strategy' => $extensionClassesPath . 'Responsive/Strategy.php',
    'tx_rtpimgquery_responsive_style' => $extensionClassesPath . 'Responsive/Style.php',
    'tx_rtpimgquery_service_compatibility' => $extensionClassesPath . 'Service/Compatibility.php',
    'tx_rtpimgquery_utility_html' => $extensionClassesPath . 'Utility/Html.php',
    'tx_rtpimgquery_utility_typoscript' => $extensionClassesPath . 'Utility/TypoScript.php',
    'tx_rtpimgquery_xclass_imagecontentobject' => $extensionClassesPath . 'Xclass/ImageContentObject.php',
);
