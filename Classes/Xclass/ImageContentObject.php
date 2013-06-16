<?php
namespace RTP\RtpImgquery\Xclass;

use \RTP\RtpImgquery\Service\Compatibility as Compatibility;

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
 */
class ImageContentObject extends \TYPO3\CMS\Frontend\ContentObject\ImageContentObject
{
    /**
     * TypoScript configuration
     *
     * @var array
     */
    private $conf;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var \RTP\RtpImgquery\Main\Image
     */
    private $defaultImage;

    /**
     * @var \RTP\RtpImgquery\imgQuery
     */
    private $imgQuery;

    /**
     * Rendering the cObject, IMAGE
     *
     * @param array TypoScript properties
     * @return string
     */
    public function render($conf = array())
    {
        $this->conf = $conf;

        if ($this->cObj->checkif($this->conf['if.'])) {

            // Gets the default image
            $this->defaultImage = $this->cObj->cImage($this->conf['file'], $this->conf);

            // Gets the breakpoints
            if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                $breakpoints = str_replace(chr(10), ',', $this->cObj->data['tx_rtpimgquery_breakpoints']);

            } else {
                $breakpoints = $this->conf['breakpoints'];
            }

            // Gets the default breakpoint
            $defaultBreakpoint = false;
            if (intval($this->cObj->data['tx_rtpimgquery_breakpoint']) > 0) {
                $defaultBreakpoint = intval($this->cObj->data['tx_rtpimgquery_breakpoint']);

            } elseif (intval($this->conf['breakpoint']) > 0) {
                $defaultBreakpoint = intval($this->conf['breakpoint']);
            }

            // Gets the pixel ratios
            if ($this->cObj->data['tx_rtpimgquery_pixel_ratios']) {
                $pixelRatios = $this->cObj->data['tx_rtpimgquery_pixel_ratios'];

            } else {
                $pixelRatios = $this->conf['breakpoints.']['pixelRatios'];
            }

            // Gets an instance of the img query class
            $this->imgQuery = Compatibility::makeInstance(
                '\RTP\RtpImgquery\imgQuery',
                $conf,
                $this->defaultImage,
                $defaultBreakpoint,
                $breakpoints,
                $pixelRatios
            );

            // Renders the responsive images
            $instance = $this;
            $this->imgQuery->render(
                function ($source, $setup) use ($instance) {
                    return $instance->cObj->cImage(
                        $source,
                        $setup
                    );
                }
            );

            // Gets the rendered responsive images or the default image
            if ($this->imgQuery->has()) {
                $imageHtml = $this->imgQuery->get();

            } else {
                $imageHtml = $this->defaultImage;
            }

            // Applies stdWrap to the imageHtml
            if (isset($conf['stdWrap.'])) {
                $imageHtml = $this->cObj->stdWrap($imageHtml, $conf['stdWrap.']);
            }

            return $imageHtml;
        }
    }
}

