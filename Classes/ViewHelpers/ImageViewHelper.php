<?php
namespace RTP\RtpImgquery\ViewHelpers;

use \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper;
use RTP\RtpImgquery\Service\Compatibility;

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
 * Extends the default Fluid image view helper to accommodate responsive and
 * fluid image techniques.
 *
 * Example:
 * {namespace responsive=Tx_RtpImgquery_ViewHelpers}
 * <responsive:image src="EXT:myext/Resources/Public/typo3_logo.png"
 *                   alt="alt text"
 *                   breakpoint="900"
 *                   breakpoints="600:300, 400"
 *                   pixelRatios="1,1.5,2" />
 *
 *
 * @author  Simon Tuck <stu@rtp.ch>
 * @link https://github.com/rtp-ch/rtp_imgquery
 * @todo: Refactor & merge with IMAGE xclass methods.
 */
class Tx_RtpImgquery_ViewHelpers_ImageViewHelper extends ImageViewHelper
{
    /**
     * @var string
     */
    const DEFAULT_STYLE = 'width: 100%; height: auto;';

    /**
     * Default layout
     *
     * @var string
     */
    const DEFAULT_LAYOUT = 'EXT:rtp_imgquery/Resources/Private/Templates/Build/html/imgQuery.html';

    /**
     * TypoScript configuration
     *
     * @var array
     */
    private $conf;

    /**
     * @var
     */
    private $imgQuery;

    /**
     * @var string
     */
    private $defaultImage;

    /**
     * @param  string $src
     * @param  null   $width
     * @param  null   $height
     * @param  null   $minWidth
     * @param  null   $minHeight
     * @param  null   $maxWidth
     * @param  null   $maxHeight
     * @param  null   $breakpoints
     * @param  null   $breakpoint
     * @param  null   $fluidStyle
     * @param  null   $pixelRatios
     * @param  null   $layout
     * @return string
     */
    public function render(
        $src,
        $width = null,
        $height = null,
        $minWidth = null,
        $minHeight = null,
        $maxWidth = null,
        $maxHeight = null,
        $breakpoints = null,
        $breakpoint = null,
        $fluidStyle = null,
        $pixelRatios = null,
        $layout = null
    ) {

        // Gets the default image
        $defaultImage = $this->getDefaultImage();

        $conf = array(
            'file.' => array(
                'width' => $width,
                'height' => $height,
                'minW' => $minWidth,
                'minH' => $minHeight,
                'maxW' => $maxWidth,
                'maxH' => $maxHeight
            )
        );

        // Gets an instance of the img query class
        $this->imgQuery = Compatibility::makeInstance(
            '\RTP\RtpImgquery\ImgQuery',
            $conf,
            $defaultImage,
            $breakpoint,
            $breakpoints,
            $pixelRatios
        );

        // Renders the responsive images
        $instance = $this;
        $this->imgQuery->render(
            function ($source, $setup) use ($instance) {
                return parent::render(
                    $source,
                    $setup['width'],
                    $setup['height']
                );
            }
        );

        // Gets the rendered responsive images or the default image
        if ($this->imgQuery->has()) {
            $imageHtml = $this->imgQuery->get();

        } else {
            $imageHtml = $this->defaultImage;
        }

        return $imageHtml;
    }

    /**
     * @param $src
     * @param $width
     * @param $height
     * @param $minWidth
     * @param $minHeight
     * @param $maxWidth
     * @param $maxHeight
     * @param $breakpoints
     * @param $breakpoint
     * @param $fluidStyle
     * @param $pixelRatios
     * @param $layout
     * @param $strategy
     */
    private function setConf(
        $src,
        $width,
        $height,
        $minWidth,
        $minHeight,
        $maxWidth,
        $maxHeight,
        $breakpoints,
        $breakpoint,
        $fluidStyle,
        $pixelRatios,
        $layout,
        $strategy
    ) {
        $this->conf['src'] = $src;
        $this->conf['width'] = $width;
        $this->conf['height'] = $height;
        $this->conf['minWidth'] = $minWidth;
        $this->conf['minHeight'] = $minHeight;
        $this->conf['maxWidth'] = $maxWidth;
        $this->conf['maxHeight'] = $maxHeight;
        $this->conf['breakpoints'] = $breakpoints;
        $this->conf['breakpoint'] = intval($breakpoint) > 0 ? intval($breakpoint) : false;
        $this->conf['fluidStyle'] = $fluidStyle;
        $this->conf['pixelRatios'] = $pixelRatios;
        $this->conf['strategy'] = $layout;
        $this->conf['strategy'] = $strategy;
    }

    /*
     * ========================================================
     * Images
     * ========================================================
     */

    /**
     * @return mixed
     */
    private function getDefaultImage()
    {
        if (is_null($this->defaultImage)) {
            $this->defaultImage = parent::render(
                $this->conf['src'],
                $this->conf['width'],
                $this->conf['height'],
                $this->conf['minWidth'],
                $this->conf['minHeight'],
                $this->conf['maxWidth'],
                $this->conf['maxHeight']
            );
        }

        return $this->defaultImage;
    }
}
