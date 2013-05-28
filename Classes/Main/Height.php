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
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    private $cObj;

    /**
     * @var
     */
    private $height;

    /**
     * @param $conf
     * @param $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     * @param $image \RTP\RtpImgquery\Main\Image
     */
    public function __construct($conf, $cObj, $image)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
        $this->image = $image;
    }

    /**
     * Gets the height of the default image from file.height of from the img HTML of the default image
     *
     * @return null
     */
    public function set()
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
            $height = $this->cObj->stdWrap(
                $this->conf['file.']['height'],
                $this->conf['file.']['height.']
            );
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

        if ($src = Html::getAttributeValue('img', 'source', $this->image)) {
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

