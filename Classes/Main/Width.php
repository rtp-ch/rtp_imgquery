<?php
namespace RTP\RtpImgquery\Main;

use \RTP\RtpImgquery\Utility\Html;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
     * @var string
     */
    private $image;

    /**
     * @var string|int
     */
    private $width;

    /**
     * @param array  $conf
     * @param string $image
     */
    public function __construct($conf, $image)
    {
        $this->conf = $conf;
        $this->image = $image;
        $this->set();
    }

    /**
     * Gets the width of the default image from the following sources (in order of priority)
     * - TypoScript configuration (e.g. file.width)
     * - width value of the image HTML
     * - Actual dimensions of the rendered image (getimagesize)
     * TODO: Get image dimensions from cObj->image object
     *
     * @return int|string
     */
    private function set()
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
        /* @var TypoScriptFrontendController $TSFE */
        global $TSFE;

        $width = false;

        if (isset($this->conf['file.']['width'])) {
            $width = $TSFE->cObj->stdWrap($this->conf['file.']['width'], $this->conf['file.']['width.']);
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

        $src = Html::getAttributeValue('img', 'source', $this->image);
        if ($src) {

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
