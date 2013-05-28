<?php
namespace RTP\RtpImgquery\Responsive;

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
 * Class Images
 * @package RTP\RtpImgquery\Responsive
 */
class Images
{

    /**
     * @var array TypoScript configuration
     */
    private $conf;

    /**
     * @var
     */
    private $images;

    /**
     * @var \RTP\RtpImgquery\Client\Breakpoints
     */
    private $breakpoints;

    /**
     * @var \RTP\RtpImgquery\Client\PixelRatios
     */
    private $pixelRatios;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    private $cObj;

    /**
     * @var Style RTP\RtpImgquery\Responsive\Style
     */
    private $style;

    /**
     * @var Configuration RTP\RtpImgquery\Responsive\Configuration
     */
    private $configuration;

    /**
     * @param $conf
     * @param $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     * @param $breakpoints \RTP\RtpImgquery\Client\Breakpoints
     * @param $pixelRatios \RTP\RtpImgquery\Client\PixelRatios
     * @param $configuration \RTP\RtpImgquery\Responsive\Configuration
     * @param $style \RTP\RtpImgquery\Responsive\Style
     */
    public function __construct($conf, $cObj, $breakpoints, $pixelRatios, $configuration, $style)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
        $this->breakpoints = $breakpoints;
        $this->pixelRatios = $pixelRatios;
        $this->configuration = $configuration;
        $this->style = $style;
    }

    /**
     * Creates the images for all breakpoints and returns a list of final image tags per breakpoint.
     *
     * @return array
     */
    public function generate()
    {
        $this->images = array();

        // Generates images according to their implied configurations by device pixel ratio and breakpoint
        if ($this->breakpoints->has()) {
            foreach ($this->pixelRatios->get() as $pixelRatio) {
                foreach ($this->breakpoints->get() as $breakpoint) {

                    // Get the implied typoscript configuration for the breakpoint
                    $impliedConfiguration = $this->configuration->getForBreakpoint($breakpoint);

                    // Multiply the width/height by pixel ratio to increase the image size.
                    if ($pixelRatio > 1) {
                        $impliedConfiguration['file.']['width']  *= $pixelRatio;
                        $impliedConfiguration['file.']['height'] *= $pixelRatio;
                    }

                    // Generate the corresponding image with the implied typoscript configuration
                    $image = $this->cObj->cImage(
                        $impliedConfiguration['file'],
                        $impliedConfiguration
                    );

                    // If set, insert inline style to make the image fluid (i.e. width/height 100%)
                    if ($this->style->has()) {
                        $image = $this->style->insert($image);
                    }

                    // Saves the generated image HTML by pixel ratio and breakpoint
                    $this->images[strval($pixelRatio)][strval($breakpoint)] = $image;
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->images;
    }

    /**
     * @return bool
     */
    public function has()
    {
        return (boolean) $this->get();
    }
}

