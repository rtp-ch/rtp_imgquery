<?php
namespace RTP\RtpImgquery\Main;

use \RTP\RtpImgquery\Service\Compatibility;
use \RTP\RtpImgquery\Utility\Html;

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
 * Class DefaultImage
 * @package RTP\RtpImgquery\Main
 */
class Height
{
    /**
     * TypoScript configuration
     *
     * @var array
     */
    private $conf;

    /**
     * @var \RTP\RtpImgquery\Main\Image
     */
    private $image;

    /**
     * @var
     */
    private $height;

    /**
     * @param $conf
     * @param $image \RTP\RtpImgquery\Main\Image
     */
    public function __construct($conf, $image)
    {
        $this->conf = $conf;
        $this->image = $image;
        $this->set();
    }

    /**
     * Gets the height of the default image from the following sources (in order of priority)
     * - TypoScript configuration (e.g. file.height)
     * - height value of the image HTML
     * - Actual dimensions of the rendered image (getimagesize)
     *
     * @return null
     */
    private function set()
    {
        $this->height = $this->getFromConfiguration();

        if (!$this->height) {
            $this->height = $this->getFromHtml();
        }

        if (!$this->height) {
            $this->height = $this->getFromImage();
        }
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->height;
    }

    /**
     * @return bool|string
     */
    public function getFromConfiguration()
    {
        $height = false;

        if (isset($this->conf['file.']['height'])) {
            $height = Compatibility::stdWrap($this->conf['file.']['height'], $this->conf['file.']['height.']);
        }

        return $height;
    }

    /**
     * @return bool|int
     */
    public function getFromHtml()
    {
        $height = Html::getAttributesFromHtml('img', 'height', $this->image);

        if (is_numeric($height)) {
            // Avoids values which are not numeric, e.g. percentages
            return intval($height);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getFromImage()
    {
        $height = false;

        $src = Html::getAttributeValue('img', 'source', $this->image);
        if ($src) {

            $imageSize = getimagesize($src);

            if (isset($imageSize[1])) {
                $height = $imageSize[1];
            }
        };

        return $height;
    }

    /**
     * @return bool
     */
    public function has()
    {
        return (boolean) $this->get();
    }
}
