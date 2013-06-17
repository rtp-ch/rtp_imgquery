<?php
namespace RTP\RtpImgquery\Responsive;

use \RTP\RtpImgquery\Service\Compatibility as Compatibility;
use \RTP\RtpImgquery\Utility\Html;

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
     * @var
     */
    private $sources;

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
     * @var \RTP\RtpImgquery\Responsive\Style
     */
    private $style;

    /**
     * @var \RTP\RtpImgquery\Responsive\Configuration
     */
    private $configuration;

    /**
     * @param $breakpoints \RTP\RtpImgquery\Client\Breakpoints
     * @param $pixelRatios \RTP\RtpImgquery\Client\PixelRatios
     * @param $configuration \RTP\RtpImgquery\Responsive\Configuration
     * @param $style \RTP\RtpImgquery\Responsive\Style
     */
    public function __construct($breakpoints, $pixelRatios, $configuration, $style)
    {
        $this->breakpoints = $breakpoints;
        $this->pixelRatios = $pixelRatios;
        $this->configuration = $configuration;
        $this->style = $style;
    }

    /**
     * Creates the images for all breakpoints and returns a list of final image tags per breakpoint.
     *
     * @param $imageRenderer
     * @return array
     */
    public function generate($imageRenderer)
    {
        $this->images = array();

        // Generates images according to their implied configurations by breakpoint
        foreach ($this->breakpoints->get() as $breakpoint) {

            // Gets the typoscript configuration for the breakpoint
            $configuration = $this->configuration->getForBreakpoint($breakpoint);

            // Generates the corresponding image from the implied typoscript
            $image = $imageRenderer(
                $configuration['file'],
                $configuration
            );

            // If set, inserts inline style to make the image fluid (i.e. width/height 100%)
            if ($this->style->has()) {
                $image = $this->style->insert($image);
            }

            // Saves the generated image HTML for the breakppoint and the default pixel ratio
            $this->images[strval(1)][strval($breakpoint)] = $image;
            // Saves the original source image
            $this->sources[strval($breakpoint)] = $configuration['file'];
        }

        // Generate higher resolution versions of the original images for each pixel ratio above 1
        if ($this->pixelRatios->has()) {
            foreach ($this->images[strval(1)] as $breakpoint => $image) {

                // Get the source image for the breakpoint
                $src = $this->sources[strval($breakpoint)];

                foreach ($this->pixelRatios->get() as $pixelRatio) {
                    if ($pixelRatio > 1) {

                        $width =  floatval($pixelRatio) * Html::getAttributeValue('img', 'width', $image);
                        $height = floatval($pixelRatio) * Html::getAttributeValue('img', 'height', $image);

                        $newImage = $imageRenderer(
                            $src,
                            array(
                                'file.' => array(
                                    'width' => $width,
                                    'height' => $height
                                )
                            )
                        );

                        $newSrc = Html::getAttributeValue('img', 'src', $newImage);
                        $modifiedImage = Html::setAttributeValue('img', 'src', $image, $newSrc);
                        $this->images[strval($pixelRatio)][$breakpoint] = $modifiedImage;
                    }
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

