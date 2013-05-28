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
 *
 * TypoScript example:
 *  10 = IMAGE
 *  10 {
 *      file = fileadmin/images/myimage.jpg
 *      file.width = 800
 *      altText = Hey, I'm responsive!
 *      params = class="enlarge"
 *      breakpoint = 1200
 *      breakpoints = 600:400,400:280,320:160
 *      breakpoints.320.file.height = 90
 *      breakpoints.pixelRatios = 1,1.5,2
 *  }
 *
 * Smarty example:
 *  {image
 *      file = "fileadmin/images/myimage.jpg"
 *      file.width = "800"
 *      altText = "Hey, I'm responsive!"
 *      params = "class=\"enlarge\""
 *      breakpoint = 1200
 *      breakpoints = 600:400,400:280,320:160
 *      breakpoints.320.file.height = 90
 *      breakpoints.pixelRatios = 1,1.5,2
 *  }
 *
 * @author  Simon Tuck <stu@rtp.ch>
 * @link https://github.com/rtp-ch/rtp_imgquery
 * @todo: Refactor & merge with view helper methods.
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
    public function set()
    {
        $this->style = false;
        $extConf = (array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rtp_imgquery']);

        // If no style has been set check the default behaviour
        if (preg_match("/^(off|false|no|none|0)$/i", $this->conf['breakpoints.']['style'])
            || !$extConf['enableFluidImages']) {

            if (isset($this->conf['breakpoints.']['style'])) {
                $this->style = $this->conf['breakpoints.']['style'];

            } else {
                $this->style = self::DEFAULT_STYLE;
            }

            // Ensures trailing semicolon in inline style
            if (substr($this->style, -1) !== ';') {
                $this->style .= ';';
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

