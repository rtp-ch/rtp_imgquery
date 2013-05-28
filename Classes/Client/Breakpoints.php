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
class Breakpoints
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
     * @var int
     */
    private $defaultBreakpoint;

    /**
     * @var array
     */
    private $breakpoints;

    /**
     * @var \RTP\RtpImgquery\Main\Width
     */
    private $defaultWidth;

    /**
     * @param $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     * @param $conf
     * @param $width \RTP\RtpImgquery\Main\Width
     */
    public function __construct($cObj, $conf, $width)
    {
        $this->conf = $conf;
        $this->defaultWidth = $width;
        $this->cObj = $cObj;
    }

    /**
     * Gets the default breakpoint from any of the following sources (in order of priority);
     * - As configured in the content element
     * - As configured in TypoScript
     * - The width of the default image.
     *
     * @return int
     */
    public function setDefault()
    {
        $this->defaultBreakpoint = false;

        if (intval($this->cObj->data['tx_rtpimgquery_breakpoint']) > 0) {
            $this->defaultBreakpoint = intval($this->cObj->data['tx_rtpimgquery_breakpoint']);

        } elseif (intval($this->conf['breakpoint']) > 0) {
            $this->defaultBreakpoint = intval($this->conf['breakpoint']);

        } elseif ($this->defaultWidth->has()) {
            $this->defaultBreakpoint = $this->defaultWidth->get();
        }
    }

    /**
     * Checks for a defined default breakpoint
     *
     * @return bool
     */
    public function getDefault()
    {
        return $this->defaultBreakpoint;
    }

    /**
     * Checks for a defined default breakpoint
     *
     * @return bool
     */
    public function hasDefault()
    {
        return (boolean) $this->getDefault();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return (array) $this->breakpoints;
    }

    /**
     * Gets the list of defined breakpoints from the configuration sorted in descending order and
     * including the default breakpoint (i.e. the breakpoint for the default image).
     *
     *
     * @return array
     */
    public function set()
    {
        // The simplest case is that breakpoints are configured as "breakpoints = x, y, z" where
        // x, y & z are the breakpoints and the breakpoints correspond exactly to the image widths. e.g.
        // 400, 600, 1000 would define image widths 400, 600 & 1000 for browser widths 400, 600 & 1000
        // Alternatively the breakpoints can be configure as "breakpoints = x:a, y:b, z:c"
        // where x, y & z are the breakpoints and a, b, c are the image widths. So 400:600 would define an
        // image width of 600 at breakpoint 400.
        if (isset($this->conf['breakpoints']) || $this->cObj->data['tx_rtpimgquery_breakpoints']) {

            if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                $this->breakpoints = str_replace(chr(10), ',', $this->cObj->data['tx_rtpimgquery_breakpoints']);

            } else {
                $this->breakpoints = $this->conf['breakpoints'];
            }

            // Create an array of breakpoints
            $this->breakpoints = GeneralUtility::trimExplode(',', $this->breakpoints, true);

            // Converts something like 610:400 to 610 (we are not interested in image widths)
            $this->breakpoints = array_filter(array_map('intval', $this->breakpoints));
        }

        // In addition to or instead of the configuration outlined above, breakpoints can be configured in more
        // detail as "breakpoints.x.file.width = n" where x is the breakpoint n is the corresponding image width.
        if (is_array($this->conf['breakpoints.'])) {

            $configuredBreakpoints = array_filter(array_map('intval', array_keys($this->conf['breakpoints.'])));

            if (is_array($configuredBreakpoints) && !empty($configuredBreakpoints)) {
                $this->breakpoints = array_merge((array) $this->breakpoints, $configuredBreakpoints);
            }
        }

        // Adds the default breakpoint to the list, but only if the list contains other breakpoints (an
        // image configuration with a single breakpoint makes no sense!)
        if (!empty($this->breakpoints)) {
            $this->breakpoints[] = $this->getDefault();
        }

        // Cleans up and sorts the final list of breakpoints
        $this->breakpoints = array_map('intval', array_unique($this->breakpoints));
        sort($this->breakpoints, SORT_NUMERIC);
    }

    /**
     * Determines if breakpoints have been defined
     *
     * @return bool
     */
    public function has()
    {
        return (boolean) $this->get();
    }

    /**
     * Returns the breakpoints settings which is either defined in TypoScript or in the
     * data of the content element.
     *
     * @return mixed
     */
    public function getSettings()
    {
        static $configuration;

        if (is_null($configuration)) {
            if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                $configuration = $this->cObj->data['tx_rtpimgquery_breakpoints'];

            } else {
                $configuration = $this->conf['breakpoints'];
            }
        }

        return $configuration;
    }
}

