<?php
namespace RTP\RtpImgquery\Main;

use \RTP\RtpImgquery\Service\Compatibility as Compatibility;
use \RTP\RtpImgquery\Utility\Html as Html;
use \RTP\RtpImgquery\Main\Image as Image;

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
class Width
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
     * @var
     */
    private $width;

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
     * Gets the height of the default image from file.width of from the img HTML of the default image
     *
     * @return int|string
     */
    public function set()
    {
        $this->width = $this->getFromConfiguration();

        if (!$this->width) {
            $this->width = $this->getFromHtml();
        }

        if (!$this->width) {
            $this->width = $this->getFromImage();
        }
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->width;
    }

    /**
     * @return bool|string
     */
    public function getFromConfiguration()
    {
        $width = false;

        if (isset($this->conf['file.']['width'])) {
            $width = $this->cObj->stdWrap(
                $this->conf['file.']['width'],
                $this->conf['file.']['width.']
            );
        }

        return $width;
    }

    /**
     * @return bool|int
     */
    public function getFromHtml()
    {
        $width = Html::getAttributesFromHtml('img', 'width', $this->image);

        if (is_numeric($width)) {
            // Avoids values which are not numeric, e.g. percentages
            return intval($width);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getFromImage()
    {
        $width = false;

        if ($src = Html::getAttributeValue('img', 'source', $this->image)) {
            $imageSize = getimagesize($src);
            if (isset($imageSize[0])) {
                $width = $imageSize[0];
            }
        };

        return $width;
    }

    /**
     * @return bool
     */
    public function has()
    {
        return (boolean) $this->get();
    }
}

