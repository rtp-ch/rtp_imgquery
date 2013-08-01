<?php
namespace RTP\RtpImgquery\Responsive;

use \RTP\RtpImgquery\Service\Compatibility;
use RTP\RtpImgquery\Utility\TypoScript;

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
 */
class Configuration
{
    /**
     * @var int
     */
    private $defaultWidth;

    /**
     * @var int
     */
    private $defaultHeight;

    /**
     * @var array TypoScript configuration
     */
    private $configuration;

    /**
     * @var string Breakpoint settings (e.g. 800:500, 1200:700)
     */
    private $settings;

    /**
     * @var int
     */
    private $defaultBreakpoint;

    /**
     * @param $configuration
     * @param $defaultWidth int
     * @param $defaultHeight int
     * @param $defaultBreakpoint
     * @param $settings string
     */
    public function __construct($configuration, $defaultWidth, $defaultHeight, $defaultBreakpoint, $settings)
    {
        $this->configuration = $configuration;
        $this->defaultWidth = $defaultWidth;
        $this->defaultHeight = $defaultHeight;
        $this->defaultBreakpoint = $defaultBreakpoint;
        $this->settings = $settings;
    }

    /**
     * @param $breakpoint
     * @return array
     */
    public function getForBreakpoint($breakpoint)
    {
        // Starts out with the TypoScript for the default configuration
        $configuration = $this->configuration;

        // Removes breakpoints typoscript which are invalid
        $configuration = TypoScript::strip($configuration);

        // Get the width implied by the breakpoint
        $configuration['file.']['width'] = $this->getWidthForBreakpoint($breakpoint);

        // Determine the height from the width
        $configuration['file.']['height'] = $this->getHeightForWidth($configuration['file.']['width']);

        // Merges in any specific configurations for the current breakpoint (e.g. breakpoints.500.x)
        if (isset($this->configuration['breakpoints.'][$breakpoint . '.']['width'])) {
            $configuration['file.'] = Compatibility::arrayMergeRecursiveOverrule(
                (array) $configuration,
                (array) $this->configuration['breakpoints.'][$breakpoint . '.']
            );
        }

        return $configuration;
    }

    /**
     * Calculates the image width for a given breakpoint based on it's ratio to the default breakpoint.
     * Takes into account special settings such as "c" and "m" (i.e. cropping and scaling parameters).
     *
     * @param $breakpoint
     * @return string
     */
    public function getWidthForBreakpoint($breakpoint)
    {
        $width = false;

        // Attempts to match a setting like 800:500 where 500 would be the image width for
        // the given breakpoint 800. In which case the width for the given breakpoint is defined.
        if ($this->settings && preg_match('/' . $breakpoint . ':(\w+)/i', $this->settings, $width)) {
            $width = TypoScript::getNewDimension($width[1], $this->defaultWidth);

        } else {
            // The image width for the given breakpoint is the relation of the default breakpoint to the
            // given breakpoint multiplied with the default width
            if ($this->defaultWidth && $this->defaultBreakpoint) {
                $width = $breakpoint / $this->defaultBreakpoint;
                $width = floor($width * intval($this->defaultWidth));
                $width = TypoScript::getNewDimension($width, $this->defaultWidth);
            }
        }

        return $width;
    }

    /**
     * Calculates the height for a given width based on the ratio between the default width and height
     *
     * @param  string     $width
     * @return int|string
     */
    public function getHeightForWidth($width)
    {
        $height = false;

        // The new height is the relation between the new width multiplied with the original height
        if ($width > 0 && $this->defaultWidth && $this->defaultHeight) {
            $height = $width / intval($this->defaultWidth);
            $height = floor($height * intval($this->defaultHeight));
            $height = TypoScript::getNewDimension($height, $this->defaultHeight);
        }

        return $height;
    }
}
