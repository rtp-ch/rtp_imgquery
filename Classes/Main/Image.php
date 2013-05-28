<?php
namespace RTP\RtpImgquery\Main;

use \RTP\RtpImgquery\Service\Compatibility as Compatibility;
use \RTP\RtpImgquery\Utility\Html as Html;

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
 * Class Image
 * @package RTP\RtpImgquery\Main
 */
class Image
{
    /**
     * TypoScript configuration
     *
     * @var array
     */
    private $conf;

    /**
     * @var
     */
    private $image;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    private $cObj;

    /**
     * @param $conf
     * @param $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public function __construct($conf, $cObj)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
    }

    /**
     * Gets the HTML of the default image
     *
     * @return string
     */
    public function get()
    {
        return $this->image;
    }

    /**
     * Generates the HTML for the default image and the renders the associated image
     */
    public function set()
    {
        $this->image = $this->cObj->cImage($this->conf['file'], $this->conf);
    }
}

