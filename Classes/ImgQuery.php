<?php
namespace RTP\RtpImgquery;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class ImgQuery
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
     * @var string Default image as rendered by cObj
     */
    private $defaultImage;

    /**
     * @var \RTP\RtpImgquery\Main\Height
     */
    private $defaultHeight;

    /**
     * @var \RTP\RtpImgquery\Main\Width
     */
    private $defaultWidth;

    /**
     * @var \RTP\RtpImgquery\Client\Breakpoints
     */
    private $breakpoints;

    /**
     * @var \RTP\RtpImgquery\Client\PixelRatios
     */
    private $pixelRatios;

    /**
     * @var \RTP\RtpImgquery\Responsive\Strategy
     */
    private $strategy;

    /**
     * @var \RTP\RtpImgquery\Responsive\Configuration
     */
    private $configuration;

    /**
     * @var \RTP\RtpImgquery\Responsive\Style
     */
    private $style;

    /**
     * @var \RTP\RtpImgquery\Main\Breakpoint
     */
    private $defaultBreakpoint;

    /**
     * @var \RTP\RtpImgquery\Responsive\Images
     */
    private $images;

    /**
     * @var
     */
    private $imageHtml = false;

    /**
     * @param $conf
     * @param $defaultImage
     * @param $defaultBreakpoint
     * @param $breakpoints
     * @param $pixelRatios
     */
    public function __construct($conf, $defaultImage, $defaultBreakpoint, $breakpoints, $pixelRatios)
    {
        $this->defaultImage = $defaultImage;
        $this->conf = $conf;

        $this->defaultWidth = GeneralUtility::makeInstance(
            '\RTP\RtpImgquery\Main\Width',
            $this->conf,
            $this->defaultImage
        );

        $this->defaultHeight = GeneralUtility::makeInstance(
            '\RTP\RtpImgquery\Main\Height',
            $this->conf,
            $this->defaultImage
        );

        $this->defaultBreakpoint = GeneralUtility::makeInstance(
            '\RTP\RtpImgquery\Main\Breakpoint',
            $this->defaultWidth->get(),
            $defaultBreakpoint
        );

        $this->breakpoints = GeneralUtility::makeInstance(
            '\RTP\RtpImgquery\Client\Breakpoints',
            $this->conf,
            $this->defaultWidth->get(),
            $this->defaultBreakpoint->get(),
            $breakpoints
        );

        $this->pixelRatios = GeneralUtility::makeInstance(
            '\RTP\RtpImgquery\Client\PixelRatios',
            $pixelRatios
        );
    }

    /**
     * @param $imageRenderer
     * @return mixed
     */
    public function render($imageRenderer)
    {
        // Checks if breakpoints and/or pixel ratios have been defined
        if ($this->defaultBreakpoint->has()
            && ($this->breakpoints->has() || $this->pixelRatios->has())) {

            // The configuration class handles respective configurations (i.e. image dimensions)
            // for different breakpoints and pixel ratios
            $this->configuration = GeneralUtility::makeInstance(
                '\RTP\RtpImgquery\Responsive\Configuration',
                $this->conf,
                $this->defaultWidth->get(),
                $this->defaultHeight->get(),
                $this->defaultBreakpoint->get(),
                $this->breakpoints->getConfiguration()
            );

            // The style class handles inline fluid style for images
            $this->style = GeneralUtility::makeInstance(
                '\RTP\RtpImgquery\Responsive\Style',
                $this->conf
            );

            // The images class handles generating various images for the defined breakpoints and pixel ratios
            $this->images = GeneralUtility::makeInstance(
                '\RTP\RtpImgquery\Responsive\Images',
                $this->breakpoints,
                $this->pixelRatios,
                $this->configuration,
                $this->style
            );

            // Generate the images, passing in a callback function to render the image with
            $this->images->generate($imageRenderer);

            // The strategy class implements the HTML/JavaScript which is inserted in place of the original image HTML
            $this->strategy = GeneralUtility::makeInstance(
                '\RTP\RtpImgquery\Responsive\Strategy',
                $this->conf
            );

            // Render the HTML/JavaScript passing in a marker array
            $this->imageHtml = $this->strategy->render(
                array(
                    'default_image' => $this->defaultImage,
                    'breakpoints' => json_encode($this->breakpoints->get()),
                    'images' => json_encode($this->images->get()),
                    'ratios' => json_encode($this->pixelRatios->get()),
                    'cache_key' => md5(serialize($this->images->get()))
                )
            );
        }
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->imageHtml;
    }

    /**
     * @return bool
     */
    public function has()
    {
        return (boolean) $this->get();
    }
}
