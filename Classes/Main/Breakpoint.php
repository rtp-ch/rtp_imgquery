<?php
namespace RTP\RtpImgquery\Main;

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
class Breakpoint
{
    /**
     * @var int
     */
    private $breakpoint;

    /**
     * @var
     */
    private $configuration;

    /**
     * @var int
     */
    private $defaultWidth;

    /**
     * @param $width int
     * @param $configuration
     */
    public function __construct($width, $configuration)
    {
        $this->defaultWidth = $width;
        $this->configuration = $configuration;
        $this->set();
    }

    /**
     * Sets the default breakpoint from the configuration or the default width
     *
     * @return int
     */
    private function set()
    {
        $this->breakpoint = $this->configuration ? $this->configuration : $this->defaultWidth;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->breakpoint;
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
}
