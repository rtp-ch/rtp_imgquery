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


class Style
{
    /**
     * Default style for responsive images
     *
     * @var string
     */
    const DEFAULT_STYLE = 'width: 100%; height: auto';

    /**
     * TypoScript configuration
     *
     * @var array
     */
    private $conf;

    /**
     * @var
     */
    private $style;

    /**
     * @param $configuration
     */
    public function __construct($configuration)
    {
        $this->conf = $configuration;
        $this->set();
    }

    /**
     * Implements inline style for a given HTML img tag.
     *
     * @param $image
     * @return mixed
     */
    public function insert($image)
    {
        if (preg_match('%<img[^>]+style\s*=\s*"[^"]+"[^>]*/?>%i', $image)) {

            // Augment an existing inline style
            $search = '%<img([^>]+)style\s*=\s*"([^"]+)"([^>]*)(/?>)%i';
            $replace = '<img$1style="$2;' . $this->get() . '"$3$4';
            $image = preg_replace($search, $replace, $image);

        } else {

            // Insert new inline style
            $image = preg_replace('%<img([^>]+)(/?>)%i', '<img style="' . $this->get() . '"$1$2', $image);
        }

        return $image;
    }

    /**
     *
     */
    private function set()
    {
        $this->style = false;
        $extConf = (array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rtp_imgquery']);

        // Fluid image style has to be enabled globally
        if ($extConf['enableFluidImages']) {

            // The style has to be defined in the TypoScript configuration
            $style = $this->conf['breakpoints.']['style'];

            // If it's enabled it can be disabled on a case-by-case basis by setting
            // a falsy or empty value
            if ($style && !preg_match("/^(off|false|no|none|0)$/i", $style)) {

                // Sets fluidStyle from the configuration
                $this->style = $style;

                // Ensures trailing semicolon in inline style
                if (substr($this->style, -1) !== ';') {
                    $this->style .= ';';
                }
            }
        }
    }

    private function setFluidStyle()
    {
        $this->fluidStyle = false;
        $extConf = (array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rtp_imgquery']);

        // Fluid image style has to be enabled globally
        if ($extConf['enableFluidImages']) {

            // The style has to be defined in the TypoScript configuration
            $style = $this->conf['breakpoints.']['style'];

            // If it's enabled it can be disabled on a case-by-case basis by setting
            // a falsy or empty value
            if ($style && !preg_match("/^(off|false|no|none|0)$/i", $style)) {

                // Sets fluidStyle from the configuration
                $this->fluidStyle = $style;

                // Ensures trailing semicolon in inline style
                if (substr($this->fluidStyle, -1) !== ';') {
                    $this->fluidStyle .= ';';
                }
            }
        }
    }

    /**
     * Gets the style attached to responsive images (the image dimensions should be fluid
     * until it hits the next breakpoint).
     *
     * @return string
     */
    public function get()
    {
        return $this->style;
    }

    /**
     * Checks if default inline styles should be applied
     *
     * @return bool
     */
    public function has()
    {
        return (boolean) $this->get();
    }
}

