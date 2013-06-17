<?php
namespace RTP\RtpImgquery\Responsive;

use \RTP\RtpImgquery\Service\Compatibility;

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
     * @var
     */
    private $strategy;

    /**
     * @var
     */
    private $markers;

    /**
     * @param $configuration
     * @param array $markers
     */
    public function __construct($configuration, array $markers = array())
    {
        $this->conf = $configuration;
        $this->set();
    }

    /**
     * Gets the responsive image HTML
     *
     * @param $markers
     * @return string
     */
    public function render($markers)
    {
        foreach ($markers as $marker => $value) {
            $this->markers['###' . strtoupper($marker) . '###'] = $value;
        }

        return html_entity_decode(
            str_ireplace(
                array_keys($this->markers),
                $this->markers,
                $this->strategy
            )
        );
    }

    /**
     * Sets the responsive image HTML from the template ("strategy") defined in the configuration
     *
     * @throws \BadMethodCallException
     * @return string
     */
    private function set()
    {
        if (isset($this->conf['breakpoints.']['layout'])) {
            // Uses "layout" for backwards compatibility
            $strategy = Compatibility::getFileAbsFileName($this->conf['breakpoints.']['layout']);

        } elseif (isset($this->conf['breakpoints.']['strategy'])) {
            // "strategy" should define a file with an HTML/JavaScritp snippet
            $strategy = Compatibility::getFileAbsFileName($this->conf['breakpoints.']['strategy']);

        } else {
            // Implements the default strategy if no other strategy was defined
            $strategy = Compatibility::getFileAbsFileName(self::DEFAULT_STRATEGY);
        }

        $this->strategy = trim(Compatibility::getURL($strategy));

        if (!$this->strategy) {
            $msg = 'Unable to read contents of responsive images strategy "' . $strategy . '"';
            throw new \BadMethodCallException($msg, 1369045646);
        }
    }
}

