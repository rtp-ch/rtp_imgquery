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
 * Class Strategy
 * @package RTP\RtpImgquery\Responsive
 */
class Strategy
{
    /**
     * Default layout
     *
     * @var string
     */
    const DEFAULT_STRATEGY = 'EXT:rtp_imgquery/Resources/Private/Templates/Build/html/imgQuery.html';

    /**
     * @param $configuration
     */
    public function __construct($configuration)
    {
        $this->conf = $configuration;
    }

    /**
     * Gets the responsive image layout
     *
     * @throws \BadMethodCallException
     * @return string
     */
    private function get()
    {
        $this->strategy = '';

        if (isset($this->conf['breakpoints.']['layout'])) {
            // Uses "layout" for backwards compatibility
            $strategy = Compatibility::getFileAbsFileName($this->conf['breakpoints.']['layout']);

        } elseif(isset($this->conf['breakpoints.']['strategy'])) {
            // "strategy" should define a file with an HTML/JavaScritp snippet
            $strategy = Compatibility::getFileAbsFileName($this->conf['breakpoints.']['strategy']);

        } else {
            // Implements the default strategy if no other strategy was defined
            $strategy = Compatibility::getFileAbsFileName(self::DEFAULT_STRATEGY);
        }

        // Gets the contents of the file
        $strategyLayout = Compatibility::getURL($strategy);

        if ($strategyLayout === false) {
            $msg = 'Unable to read contents of responsive images strategy "' . $strategy . '"';
            throw new \BadMethodCallException($msg, 1369045646);
        }
    }
}

