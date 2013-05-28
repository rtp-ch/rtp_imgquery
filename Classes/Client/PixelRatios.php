<?php
namespace RTP\RtpImgquery\Client;

use \TYPO3\CMS\Core\Utility\GeneralUtility as GeneralUtility;

/* ============================================================================
 *
 * This script is part of the rtp_imgquery extension ("responsive
 * images for TYPO3") for the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Copyright 2012 Simon Tuck <stu@rtp.ch>
 *
 * ============================================================================
 */

/**
 * Extends the TypoScript IMAGE object to accommodate responsive and
 * fluid image techniques. Automatically adds equivalent functionality
 * to the smarty image plugin.
 *
 * TypoScript example:
 *  10 = IMAGE
 *  10 {
 *      file = fileadmin/images/myimage.jpg
 *      file.width = 800
 *      altText = Hey, I'm responsive!
 *      params = class="enlarge"
 *      breakpoint = 1200
 *      breakpoints = 600:400,400:280,320:160
 *      breakpoints.320.file.height = 90
 *      breakpoints.pixelRatios = 1,1.5,2
 *  }
 *
 * Smarty example:
 *  {image
 *      file = "fileadmin/images/myimage.jpg"
 *      file.width = "800"
 *      altText = "Hey, I'm responsive!"
 *      params = "class=\"enlarge\""
 *      breakpoint = 1200
 *      breakpoints = 600:400,400:280,320:160
 *      breakpoints.320.file.height = 90
 *      breakpoints.pixelRatios = 1,1.5,2
 *  }
 *
 * @author  Simon Tuck <stu@rtp.ch>
 * @link https://github.com/rtp-ch/rtp_imgquery
 * @todo: Refactor & merge with view helper methods.
 */
class PixelRatios
{
    /**
     * @var array TypoScript configuration
     */
    private $conf;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var
     */
    private $pixelRatios;

    /**
     * @param $cObj
     * @param $conf
     */
    public function __construct($cObj, $conf)
    {
        $this->cObj = $cObj;
        $this->conf = $conf;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->pixelRatios;
    }

    /**
     * Returns an array of configured retina ratios to be used for image generation.
     *
     * @return array list of the configured retina ratios, will always include the default ratio "1"
     */
    public function set()
    {
        if ($this->cObj->data['tx_rtpimgquery_pixel_ratios']) {
            $this->pixelRatios = GeneralUtility::trimExplode(
                ',',
                $this->cObj->data['tx_rtpimgquery_pixel_ratios'],
                true
            );

        } else {
            $this->pixelRatios = GeneralUtility::trimExplode(
                ',',
                $this->conf['breakpoints.']['pixelRatios'],
                true
            );
        }

        // The default device resolution of 1 is always set!
        array_unshift($this->pixelRatios, 1);

        // Creates a list of unique values
        $this->pixelRatios = array_unique(array_map('floatval', $this->pixelRatios));
        sort($this->pixelRatios);
    }
}

