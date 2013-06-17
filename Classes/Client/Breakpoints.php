<?php
namespace RTP\RtpImgquery\Client;

use RTP\RtpImgquery\Service\Compatibility;

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
     * @var array
     */
    private $breakpoints;

    /**
     * @var int
     */
    private $defaultWidth;

    /**
     * @var
     */
    private $defaultBreakpoint;

    /**
     * @var
     */
    private $configuration;

    /**
     * @param $conf
     * @param $defaultWidth int
     * @param $defaultBreakpoint int
     * @param $configuration string
     */
    public function __construct($conf, $defaultWidth, $defaultBreakpoint, $configuration)
    {
        $this->conf = $conf;
        $this->defaultWidth = $defaultWidth;
        $this->defaultBreakpoint = $defaultBreakpoint;
        $this->configuration = $configuration;
        $this->set();
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
     * The simplest case is that breakpoints are configured as "breakpoints = x, y, z" where
     * x, y & z are the breakpoints and the breakpoints correspond exactly to the image widths. e.g.
     * 400, 600, 1000 would define image widths 400, 600 & 1000 for browser widths 400, 600 & 1000
     * Alternatively the breakpoints can be configure as "breakpoints = x:a, y:b, z:c"
     * where x, y & z are the breakpoints and a, b, c are the image widths. So 400:600 would define an
     * image width of 600 at breakpoint 400.
     *
     * @return array
     */
    private function set()
    {
        // Create an array of breakpoints
        $this->breakpoints = Compatibility::trimExplode(',', $this->getConfiguration(), true);

        // Converts something like 610:400 to 610 (we are not interested in image widths)
        $this->breakpoints = array_filter(array_map('intval', $this->breakpoints));

        // In addition to or instead of the configuration outlined above, breakpoints can be configured in more
        // detail as "breakpoints.x.file.width = n" where x is the breakpoint n is the corresponding image width.
        if (is_array($this->conf['breakpoints.'])) {

            $configuredBreakpoints = array_filter(array_map('intval', array_keys($this->conf['breakpoints.'])));

            if (is_array($configuredBreakpoints) && !empty($configuredBreakpoints)) {
                $this->breakpoints = array_merge((array) $this->breakpoints, (array) $configuredBreakpoints);
            }
        }

        // Adds the default breakpoint to the list
        $this->breakpoints[] = $this->defaultBreakpoint;

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
        return count($this->get()) > 1;
    }

    /**
     * Returns the breakpoints settings which is either defined in TypoScript or in the
     * data of the content element.
     *
     * @return string
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}

