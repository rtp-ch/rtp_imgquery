<?php
namespace RTP\RtpImgquery\Client;

use RTP\RtpImgquery\Utility\Collection;

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
class PixelRatios
{
    /**
     * @var array TypoScript configuration
     */
    private $configuration;

    /**
     * @var array
     */
    private $pixelRatios;

    /**
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
        $this->set();
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->pixelRatios;
    }

    /**
     * Determines if additional pixel ratios (other than the default of 1) have been defined
     *
     * @return bool
     */
    public function has()
    {
        return count($this->get()) > 1;
    }

    /**
     * Returns an array of configured retina ratios to be used for image generation.
     *
     * @return array list of the configured retina ratios, will always include the default ratio "1"
     */
    private function set()
    {
        // Gets the pixel ratios from a comma separated list
        $this->pixelRatios = Collection::trimExplode($this->configuration, ',');

        // The default device resolution of 1 is always set!
        array_unshift($this->pixelRatios, 1);

        // Creates a list of unique values
        $this->pixelRatios = array_unique(array_map('floatval', $this->pixelRatios));
        sort($this->pixelRatios);
    }
}
