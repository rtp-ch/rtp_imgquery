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
 * Extends the TypoScript IMAGE object to accommodate responsive and
 * fluid image techniques. Automatically adds equivalent functionality
 * to the smarty image plugin.
 */
class Configuration
{
    /**
     * @var \RTP\RtpImgquery\Main\Width
     */
    private $defaultWidth;

    /**
     * @var \RTP\RtpImgquery\Main\Height
     */
    private $defaultHeight;

    /**
     * @var array TypoScript configuration
     */
    private $conf;

    /**
     * @var \RTP\RtpImgquery\Client\Breakpoints
     */
    private $breakpoints;

    /**
     * @param $conf
     * @param $defaultWidth \RTP\RtpImgquery\Main\Width
     * @param $defaultHeight \RTP\RtpImgquery\Main\Height
     * @param $breakpoints \RTP\RtpImgquery\Client\Breakpoints
     */
    public function __construct($conf, $defaultWidth, $defaultHeight, $breakpoints)
    {
        $this->conf = $conf;
        $this->breakpoints = $breakpoints;
        $this->defaultWidth = $defaultWidth;
        $this->defaultHeight = $defaultHeight;
    }

    /**
     * @param $breakpoint
     * @return array
     */
    public function getForBreakpoint($breakpoint)
    {
        // Starts out with the TypoScript for the default configuration
        $configurationForBreakpoint = $this->conf;

        // Unsets "breakpoint/breakpoints" definitions which are not valid for image objects
        unset($configurationForBreakpoint['breakpoints']);
        unset($configurationForBreakpoint['breakpoints.']);

        // Unsets additional image dimension settings which would disrupt the implied measurements
        // for the current breakpoint.
        unset($configurationForBreakpoint['file.']['maxW']);
        unset($configurationForBreakpoint['file.']['maxH']);
        unset($configurationForBreakpoint['file.']['minW']);
        unset($configurationForBreakpoint['file.']['minH']);

        // Gets the breakpoint settings (e.g. 800:500, 1200:700) from the content element or from TypoScript
        $breakpointSettings = $this->breakpoints->getSettings();

        // Attempts to match a setting like 800:500 where 500 would be the image width for
        // the given breakpoint 800. In which case the width for the given breakpoint is defined.
        if ($breakpointSettings && preg_match('/' . $breakpoint . ':(\w+)/i', $breakpointSettings, $width)) {
            $configurationForBreakpoint['file.']['width'] = $width[1];

        } else {
            // Otherwise the width has to be inferred from the default width, the default breakpoint
            // and the given breakpoint.
            $configurationForBreakpoint['file.']['width'] = $this->getWidthImpliedByBreakpoint($breakpoint);
        }

        // Determine the height from the width
        $configurationForBreakpoint['file.']['height'] = $this->getHeightImpliedByWidth(
            $configurationForBreakpoint['file.']['width']
        );

        // Merges in any specific configurations for the current breakpoint (e.g. breakpoints.500.x)
        if (isset($this->conf['breakpoints.'][$breakpoint . '.'])) {
            $configurationForBreakpoint['file.'] = Compatibility::arrayMergeRecursiveOverrule(
                (array) $configurationForBreakpoint,
                (array) $this->conf['breakpoints.'][$breakpoint . '.']
            );
        }

        return $configurationForBreakpoint;
    }

    /**
     * Calculates the image width for a given breakpoint based on it's ratio to the default breakpoint.
     * Takes into account special settings such as "c" and "m" (i.e. cropping and scaling parameters).
     *
     * @param $breakpoint
     * @return string
     */
    public function getWidthImpliedByBreakpoint($breakpoint)
    {
        $widthForBreakpoint = false;

        // The image width for the given breakpoint is the relation of the default breakpoint to the
        // given breakpoint multiplied with the default width
        if ($this->defaultWidth->has() && $this->breakpoints->hasDefault()) {
            $widthForBreakpoint = $breakpoint / $this->breakpoints->getDefault();
            $widthForBreakpoint = floor($widthForBreakpoint * intval($this->defaultWidth->get()));
            $widthForBreakpoint = preg_replace('/^\d+/', $widthForBreakpoint, $this->defaultWidth->get());
        }

        return $widthForBreakpoint;
    }

    /**
     * Calculates the height for a given width based on the ratio between the default width and height
     *
     * @param string $width
     * @return int|string
     */
    public function getHeightImpliedByWidth($width)
    {
        $heightForWidth = false;

        // The new height is the relation between the new width multiplied with the original height
        if ($width > 0 && $this->defaultWidth->has() && $this->defaultHeight->has()) {
            $heightForWidth = $width / intval($this->defaultWidth->get());
            $heightForWidth = floor($heightForWidth * intval($this->defaultHeight->get()));
            $heightForWidth = preg_replace('/\d+/', $heightForWidth, $this->defaultHeight->get());
        }

        return $heightForWidth;
    }
}

