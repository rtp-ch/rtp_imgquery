<?php
use \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper as ImageViewHelper;
use \TYPO3\CMS\Core\Utility\GeneralUtility as GeneralUtility;

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
class Tx_RtpImgquery_ViewHelpers_ImageViewHelper extends Tx_Fluid_ViewHelpers_ImageViewHelper
{
    /**
     * @var string
     */
    const IMAGE_STYLE = 'width: 100%; height: auto;';

    /**
     * Default layout
     *
     * @var string
     */
    const DEFAULT_LAYOUT = 'EXT:rtp_imgquery/Resources/Private/Templates/Build/html/imgQuery.html';

    /**
     * Initial content of responsive images layouts
     *
     * @var array
     */
    private static $layoutContent;

    /**
     * TypoScript configuration
     *
     * @var array
     */
    private $conf;

    /**
     * Image id
     *
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $images;

    /**
     * @var array
     */
    private $breakpoints;

    /**
     * @var int
     */
    private $defaultWidth;

    /**
     * @var string
     */
    private $defaultImage;

    /**
     * @var int
     */
    private $defaultBreakpoint;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $breakpointConfigurations;

    /**
     * @var string
     */
    private $layout;

    /**
     * @var array
     */
    private $markers;

    /**
     * @var
     */
    private $pixelRatios;

    /**
     * @var string
     */
    private $defaultSource;

    /**
     * @param string $src
     * @param null $width
     * @param null $height
     * @param null $minWidth
     * @param null $minHeight
     * @param null $maxWidth
     * @param null $maxHeight
     * @param null $breakpoints
     * @param null $breakpoint
     * @param null $pixelRatios
     * @param null $layout
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
        $pixelRatios = null,
        $layout = null
    ) {

        $this->setConf(
            $src,
            $width,
            $height,
            $minWidth,
            $minHeight,
            $maxWidth,
            $maxHeight,
            $breakpoints,
            $breakpoint,
            $pixelRatios,
            $layout
        );

        if ($this->hasBreakpoints()) {
            // TODO: An option to define/override the style
            $this->tag->addAttribute('style', self::IMAGE_STYLE);
            $imageHtml = $this->responsiveImage();

            // Otherwise create the default image
        } else {
            $imageHtml = $this->defaultImage();
        }

        return $imageHtml;
    }

    /*
     * ========================================================
     * Main
     * ========================================================
     */

    /**
     * @return mixed|string
     */
    private function responsiveImage()
    {
        if (count($this->breakpoints()) > 1) {
            $search  = array_keys($this->markers());
            $replace = $this->markers();
            $content = $this->layoutContent();
            $responsiveImage = html_entity_decode(str_ireplace($search, $replace, $content));

        } else {
            $responsiveImage = $this->defaultImage();
        }

        return $responsiveImage;
    }

    /*
     * ========================================================
     * Breakpoints
     * ========================================================
     */

    /**
     * @return bool
     */
    private function hasDefaultBreakpoint()
    {
        return (boolean) $this->conf['breakpoint'];
    }

    /**
     * @return bool|int|string
     */
    private function defaultBreakpoint()
    {
        if (is_null($this->defaultBreakpoint)) {
            if ($this->conf['breakpoint']) {
                $this->defaultBreakpoint = $this->conf['breakpoint'];

            } else {
                $this->defaultBreakpoint = $this->defaultWidth();
            }
        }

        return $this->defaultBreakpoint;
    }

    /**
     * Determines if breakpoints have been defined
     *
     * @return bool
     */
    private function hasBreakpoints()
    {
        return (boolean)$this->breakpoints();
    }

    /**
     * Gets the list of defined breakpoints sorted in descending order and including the default breakpoint (i.e. the
     * breakpoint for the default image).
     *
     * @return array
     */
    private function breakpoints()
    {
        if (is_null($this->breakpoints)) {

            $breakpoints = t3lib_div::trimExplode(',', $this->conf['breakpoints'], true);

            // Adds the default breakpoint to the list if a breakpoint configuration exists or
            // breakpoints have been defined.
            if (!empty($breakpoints) || $this->hasDefaultBreakpoint()) {
                $breakpoints[] = $this->defaultBreakpoint();
                // Ensures the list is unique and converts values like 610:400 to 610
                $breakpoints = array_map('intval', array_unique($breakpoints));
                // Sorts the list numerically in ascending order
                sort($breakpoints, SORT_NUMERIC);
            }

            $this->breakpoints = $breakpoints;
        }

        return $this->breakpoints;
    }

    private function breakpointConfiguration($breakpoint)
    {
        $breakpointConfigurations = $this->breakpointConfigurations();
        return $breakpointConfigurations[$breakpoint];
    }

    /**
     * Gets the configured TypoScript for all breakpoints (breakpoints.[...]).
     *
     * @return array
     */
    private function breakpointConfigurations()
    {
        if (is_null($this->breakpointConfigurations)) {

            $this->breakpointConfigurations = array();

            if ($this->hasBreakpoints()) {

                // Breakpoints configuration
                if (isset($this->conf['breakpoints'])) {

                    // Gets the list of breakpoints and their respective widths
                    $breakpoints = t3lib_div::trimExplode(',', $this->conf['breakpoints'], true);

                    // Validation and width configuration for each breakpoint
                    while ($breakpoint = array_shift($breakpoints)) {

                        $breakpointSettings = t3lib_div::trimExplode(':', $breakpoint, true, 2);
                        $breakpointValue = intval($breakpointSettings[0]);

                        if ($breakpointValue > 0) {

                            // The image width for the breakpoint has either been defined or is derived
                            // from the default width and default breakpoint.
                            if (intval($breakpointSettings[1]) > 0) {
                                $breakpointWidth = intval($breakpointSettings[1]);
                            } else {
                                $breakpointWidth = $this->getBreakpointWidth($breakpointValue);
                            }
                            $this->breakpointConfigurations[$breakpointValue] = $breakpointWidth;
                        }
                    }
                }

                // Default breakpoint
                $this->breakpointConfigurations[$this->defaultBreakpoint()] = $this->defaultWidth();

                // Sorts the configurations descending by breakpoint
                krsort($this->breakpointConfigurations);
            }
        }

        return $this->breakpointConfigurations;
    }

    /*
     * =======================================================
     * Retina Images
     * =======================================================
     */

    /**
     * Returns an array of configured retina ratios to be used for image generation.
     *
     * @return array list of the configured retina ratios, will always include the default ratio "1"
     */
    private function pixelRatios()
    {
        if (is_null($this->pixelRatios)) {

            $this->pixelRatios = array();

            if (isset($this->conf['pixelRatios'])) {
                $this->pixelRatios = GeneralUtility::trimExplode(',', $this->conf['pixelRatios'], true);
            }

            // The default device resolution is 1
            array_unshift($this->pixelRatios, 1);

            // Caches a list of unique values
            $this->pixelRatios = array_unique(array_map('floatval', $this->pixelRatios));
            sort($this->pixelRatios);
        }

        return $this->pixelRatios;
    }

    /*
     * ========================================================
     * Image dimensions
     * ========================================================
     */

    /**
     * @return bool
     */
    private function hasDefaultWidth()
    {
        return (boolean)$this->defaultWidth();
    }

    /**
     * @return bool|int|string
     */
    private function defaultWidth()
    {
        if (is_null($this->defaultWidth)) {
            $this->defaultWidth = false;
            if (preg_match('/width\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if (is_numeric($match[1])) {
                    $this->defaultWidth = $match[1];

                } elseif ($this->defaultSource()) {
                    // TODO: Get image dimensions from image source
                }
            }
        }

        return $this->defaultWidth;
    }

    /**
     * @return bool
     */
    private function hasDefaultHeight()
    {
        return (boolean)$this->defaultHeight();
    }

    private function defaultHeight()
    {
        if (is_null($this->defaultHeight)) {
            $this->defaultHeight = false;
            if (preg_match('/height\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if (is_numeric($match[1])) {
                    $this->defaultHeight = $match[1];
                } elseif ($this->defaultSource()) {
                    // TODO: Get image dimensions from image source
                }
            }
        }

        return $this->defaultHeight;
    }

    /**
     * @param $breakpoint
     * @return float
     */
    private function getBreakpointWidth($breakpoint)
    {
        if ($this->hasDefaultWidth()) {
            return floor(($breakpoint / $this->defaultBreakpoint()) * $this->defaultWidth());
        }
    }

    private function getHeightForWidth($width)
    {
        if ($this->hasDefaultWidth() && $this->hasDefaultHeight()) {
            return ($width / $this->defaultWidth()) * $this->defaultHeight();
        }
    }

    /*
     * ========================================================
     * Configuration
     * ========================================================
     */

    /**
     * Instance id: a unique Id for each IMAGE object.
     *
     * @return string
     */
    private function id()
    {
        if (is_null($this->id)) {
            $this->id = md5(uniqid(mt_rand(), true));
        }
        return $this->id;
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
     * @param $layout
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
        $pixelRatios,
        $layout
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
        $this->conf['pixelRatios'] = $pixelRatios;
        $this->conf['layout'] = $layout;
    }

    /*
     * ========================================================
     * Images
     * ========================================================
     */

    /**
     * @return mixed
     */
    public function defaultImage()
    {
        if (is_null($this->defaultImage)) {
            $this->defaultImage = parent::render(
                $this->conf['src'],
                $this->conf['width'],
                $this->conf['height'],
                $this->conf['minWidth'],
                $this->conf['minHeight'],
                $this->conf['maxWidth'],
                $this->conf['maxHeight'],
                $this->conf['breakpoints'],
                $this->conf['breakpoint'],
                $this->conf['pixelRatios']
            );
        }

        return $this->defaultImage;
    }

    /**
     * TODO: Use this to determine default image dimensions when otherwise unavailable
     *
     * @return string
     */
    private function defaultSource()
    {
        if (is_null($this->defaultSource)) {
            $this->defaultSource = false;
            if (preg_match('/src\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                $defaultSource = t3lib_div::getFileAbsFileName($match[1]);
                if (is_readable($defaultSource)) {
                    $this->defaultSource = $defaultSource;
                }
            }
        }
        return $this->defaultSource;
    }

    /**
     * Retrieves the image tag for a given breakpoint.
     *
     * @param $breakpoint
     * @return null
     */
    private function image($breakpoint)
    {
        $images = $this->images();
        if (is_string($images[$breakpoint])) {
            $image = $images[$breakpoint];
        } else {
            $image = null;
        }
        return $image;
    }

    /**
     * Creates the images for all breakpoints and returns a list of final image tags per breakpoint.
     *
     * @return array
     */
    private function images()
    {
        if (is_null($this->images)) {

            $this->images = array();

            if ($this->hasBreakpoints()) {
                foreach ($this->pixelRatios() as $pixelRatio) {
                    // Renders an image for each breakpoint/width combination
                    foreach ($this->breakpoints() as $breakpoint) {
                        $width = $this->breakpointConfiguration($breakpoint) * $pixelRatio;
                        $this->images[strval($pixelRatio)][$breakpoint] = parent::render(
                            $this->conf['src'],
                            $width,
                            $this->getHeightForWidth($width)
                        );
                    }
                }
            }
        }

        return $this->images;
    }

    /*
     * ========================================================
     * Attributes
     * ========================================================
     */

    /**
     * Gets attribute/value pairs by breakpoint, i.e. returns all the attributes of an image for
     * a given breakpoint.
     *
     * @param $breakpoint
     * @return array
     */
    private function attribute($breakpoint)
    {
        $attributes = $this->attributes();
        if (is_array($attributes[$breakpoint]) && !empty($attributes[$breakpoint])) {
            $attribute = $attributes[$breakpoint];
        } else {
            $attribute = null;
        }
        return $attribute;
    }

    /**
     * Gets a list of image attributes and their values by breakpoint
     *
     * @return array
     */
    private function attributes()
    {
        if (is_null($this->attributes)) {
            $this->attributes = array();
            foreach ($this->images() as $breakpoint => $image) {
                // http://stackoverflow.com/questions/317053/regular-expression-for-extracting-tag-attributes
                if (preg_match_all('/(\S+)=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/s', $image, $attributes)) {
                    $this->attributes[$breakpoint] = array_combine($attributes[1], $attributes[2]);
                }
            }
        }
        return $this->attributes;
    }

    /*
     * ========================================================
     * Template
     * ========================================================
     */

    /**
     * Gets the initial content of the current responsive image layout
     *
     * @return string
     */
    private function layoutContent()
    {
        if (is_null(self::$layoutContent[$this->layout()])) {
            self::$layoutContent[$this->layout()] = t3lib_div::getURL($this->layout());
        }
        return self::$layoutContent[$this->layout()];
    }

    /**
     * Gets the responsive image layout
     *
     * @return array
     */
    private function layout()
    {
        if (is_null($this->layout)) {
            $this->layout = t3lib_div::getFileAbsFileName(self::DEFAULT_LAYOUT);
            if (isset($this->conf['layout'])) {
                $layout = $this->conf['layout'];
                if (is_readable($layout)) {
                    $this->layout = $layout;
                }
            }
        }
        return $this->layout;
    }

    /**
     * Sets list of markers which are inserted into the responsive image layout
     *
     * @return array
     */
    private function markers()
    {
        if (is_null($this->markers)) {
            $this->markers = array(
                '###DEFAULT_IMAGE###' => $this->defaultImage(),
                '###BREAKPOINTS###' => json_encode($this->breakpoints()),
                '###IMAGES###' => json_encode($this->images()),
                '###RATIOS###' => json_encode($this->pixelRatios()),
            );
        }
        return $this->markers;
    }
}

