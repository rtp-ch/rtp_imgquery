<?php
namespace RTP\RtpImgquery\Xclass;

use \TYPO3\CMS\Core\Utility\GeneralUtility as GeneralUtility;
use \RTP\RtpImgquery\Configuration\Cache as Cache;

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
class ImageContentObject extends \TYPO3\CMS\Frontend\ContentObject\ImageContentObject
{
    /**
     * Registry retains information about generated images
     *
     * @var null
     */
    private $cache;

    /**
     * Default layout
     *
     * @var string
     */
    const DEFAULT_LAYOUT = 'EXT:rtp_imgquery/Resources/Private/Templates/Build/html/imgQuery.html';

    /**
     * Default style for responsive images
     *
     * @var string
     */
    const DEFAULT_STYLE = 'width: 100%; height: auto';

    /**
     * Initial content of responsive images layouts
     *
     * @var array
     */
    private $layoutContent;

    /**
     * @var
     */
    private $defaultImage;

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
     * @var tslib_content
     */
    public $cObj;

    /**
     * Rendering the cObject, IMAGE
     *
     * @param    array        Array of TypoScript properties
     * @return    string        Output
     */
    public function render($conf = array())
    {
        // Initialize the IMAGE object. Note that "tslib_content_Image" is implemented as a singleton
        // so a variable ($registry) and a unique id are used to store the details of individual IMAGE objects.
        $this->conf = $conf;
        $this->defaultImage = $this->cObj->cImage($this->conf['file'], $this->conf);
        $cacheIdentity = array($this->defaultImage, $conf);
        $this->cache = GeneralUtility::makeInstance('RTP\RtpImgquery\Configuration\Cache', $cacheIdentity);

        if ($this->cObj->checkif($conf['if.'])) {

            if ($this->hasDebug()) {
                \t3lib_utility_Debug::debug(
                    array(
                        '================' => '================',
                        'conf' => $this->conf,
                        'hasBreakpoints' => $this->hasBreakpoints(),
                        'tx_rtpimgquery_breakpoints' => $this->cObj->data['tx_rtpimgquery_breakpoints'],
                        'tx_rtpimgquery_breakpoint' => $this->cObj->data['tx_rtpimgquery_breakpoint'],
                    )
                );
            }

            // If breakpoints have been defined in the TypoScript configuration create
            // a responsive version of the image
            if ($this->hasBreakpoints()) {

                $imageHtml = $this->responsiveImage();

                if ($this->hasDebug()) {
                    \t3lib_utility_Debug::debug(
                        array(
                            'defaultImage' => $this->defaultImage(),
                            'defaultWidth' => $this->defaultWidth(),
                            'breakpoints' => $this->breakpoints(),
                            'defaultBreakpoint' => $this->defaultBreakpoint(),
                            'impliedConf' => $this->impliedConfigurations(),
                            'images' => $this->images(),
                            'markers' => $this->markers()
                        )
                    );
                }

            } else {
                // Otherwise create the default image
                $imageHtml = $this->defaultImage();
            }

            if (isset($conf['stdWrap.'])) {
                $imageHtml = $this->cObj->stdWrap($imageHtml, $conf['stdWrap.']);
            }

            if ($this->hasDebug()) {
                \t3lib_utility_Debug::debug(
                    array(
                        'imageHtml' => $imageHtml,
                        '================' => '================',
                    )
                );
            }

            return $imageHtml;
        }
    }

    /*
     * ========================================================
     * Main
     * ========================================================
     */

    /**
     * Parses and returns the responsive image layout content.
     *
     * @return string
     */
    private function responsiveImage()
    {
        if (count($this->breakpoints()) > 1) {
            $search = array_keys($this->markers());
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
     * Image attributes
     * ========================================================
     */

    /**
     * Implements inline style for a given HTML img tag.
     *
     * @param $image
     * @return mixed
     */
    private function insertStyle($image)
    {
        // If set, handle inline style to make the image fluid (i.e. width/height 100%)
        if ($this->hasStyle()) {

            if (preg_match('%<img[^>]+style\s*=\s*"[^"]+"[^>]*/?>%i', $image)) {
                // Augment an existing inline style
                $search = '%<img([^>]+)style\s*=\s*"([^"]+)"([^>]*)(/?>)%i';
                $replace = '<img$1style="$2;' . $this->getStyle() . '"$3$4';
                $image = preg_replace($search, $replace, $image);

            } else {

                // Or insert new inline style
                $image = preg_replace('%<img([^>]+)(/?>)%i', '<img style="' . $this->getStyle() . '"$1$2', $image);
            }
        }

        return $image;
    }

    /**
     * Gets the style attached to responsive images (the image dimensions should be fluid
     * until it hits the next breakpoint).
     *
     * @return string
     */
    private function getStyle()
    {

        if (!$this->cache->has('style')) {

            if (isset($this->conf['breakpoints.']['style']) && (boolean)$this->conf['breakpoints.']['style']) {
                $style = $this->conf['breakpoints.']['style'];

            } else {
                $style = self::DEFAULT_STYLE;
            }

            // Ensures trailing semicolon in inline style
            if (substr($style, -1) !== ';') {
                $style .= ';';
            }

            $this->cache->set('style', $style);
        }

        return $this->cache->get('style', $style);
    }

    /**
     * Checks if default inline styles should be applied
     *
     * @return bool
     */
    private function hasStyle()
    {
        $hasStyle = false;

        if ($this->hasBreakpoints()) {

            $style = trim($this->conf['breakpoints.']['style']);

            if ((boolean)$style) {

                // If a style has been set and that style is falsey value then fluid image style is disabled
                if (preg_match("/^(off|false|no|none|0)$/i", $style)) {
                    $hasStyle = false;

                } else {
                    $hasStyle = true;
                }

            } else {
                // If no style has been set check the default behaviour
                $extConf = (array)unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rtp_imgquery']);
                $hasStyle = (boolean)$extConf['enableFluidImages'] ? true : false;
            }
        }

        return $hasStyle;
    }

    /*
     * ========================================================
     * Images
     * ========================================================
     */

    /**
     * * Gets the img HTML for the default image.
     *
     * @return string
     */
    private function defaultImage()
    {
        if (!$this->cache->has('defaultImage')) {

            $defaultImage = $this->cObj->cImage($this->conf['file'], $this->conf);
            $defaultImage = $this->insertStyle($defaultImage);
            $this->cache->set('defaultImage', $defaultImage);
        }

        return $this->cache->get('defaultImage');
    }


    /**
     * Creates the images for all breakpoints and returns a list of final image tags per breakpoint.
     *
     * @return array
     */
    private function images()
    {
        if (!$this->cache->has('images')) {

            $images = array();

            // Generates images according to their implied configurations by device pixel ratio and breakpoint
            if ($this->hasBreakpoints()) {
                foreach ($this->pixelRatios() as $pixelRatio) {
                    foreach ($this->breakpoints() as $breakpoint) {

                        // Get the implied typoscript configuration for the breakpoint
                        $impliedConfiguration = $this->impliedConfiguration($breakpoint);

                        if ($pixelRatio > 1) {
                            $standardWidth = $impliedConfiguration['file.']['width'];
                            $impliedConfiguration['file.']['width'] = $pixelRatio * $standardWidth;

                            $standardHeight = $impliedConfiguration['file.']['height'];
                            $impliedConfiguration['file.']['height'] = $pixelRatio * $standardHeight;
                        }

                        // Generate the corresponding image with the implied typoscript configuration
                        $image = $this->cObj->cImage($impliedConfiguration['file'], $impliedConfiguration);

                        // Implements inline styles
                        $image = $this->insertStyle($image);

                        //
                        $images[strval($pixelRatio)][strval($breakpoint)] = $image;
                    }
                }
            }

            $this->cache->set('images', $images);
        }

        return $this->cache->get('images');
    }

    /*
     * ========================================================
     * Configuration
     * ========================================================
     */

    /**
     * Checks if debugging is enabled
     *
     * @return bool
     */
    private function hasDebug()
    {
        if (!$this->cache->has('debug')) {

            $hasDebug = false;

            if (isset($this->conf['breakpoints.']['debug']) && (boolean)$this->conf['breakpoints.']['debug']) {
                if (preg_match("/^(on|true|yes|1)$/i", trim($this->conf['breakpoints.']['debug']))) {
                    $hasDebug = true;
                }
            }

            $this->cache->set('debug', $hasDebug);
        }

        return $this->cache->get('debug');
    }

    /**
     * Gets the implied TypoScript IMAGE configuration for a given breakpoint.
     *
     * @param $breakpoint
     * @return array
     */
    private function impliedConfiguration($breakpoint)
    {
        $impliedConfigurations = $this->impliedConfigurations();

        if (is_array($impliedConfigurations[$breakpoint]) && !empty($impliedConfigurations[$breakpoint])) {
            $impliedConfiguration = $impliedConfigurations[$breakpoint];

        } else {
            $impliedConfiguration = null;
        }

        return $impliedConfiguration;
    }

    /**
     * Creates and returns a list (sorted in ascending order) of breakpoints and their complete
     * TypoScript IMAGE configurations. These configurations are implied by th configuration of the
     * default image, (i.e. $this->conf without any of the "breakpoint/breakpoints" settings) and their
     * implied measurements.
     *
     * @return array
     */
    private function impliedConfigurations()
    {
        // Creates the TypoScript configuration for each breakpoint image from the TypoScript configuration
        // of the default image and any breakpoint TypoScript configuration.
        if (!$this->cache->has('impliedConfigurations')) {

            $impliedConfigurations = array();

            if ($this->hasBreakpoints()) {
                foreach ($this->breakpoints() as $breakpoint) {

                    // Copies default IMAGE TypoScript to each breakpoint.
                    $impliedConfigurations[$breakpoint] = $this->conf;

                    // Modifies image dimensions for all images (including the default)
                    $impliedConfigurations[$breakpoint] =
                        GeneralUtility::array_merge_recursive_overrule(
                            $impliedConfigurations[$breakpoint],
                            $this->impliedMeasurement($breakpoint)
                        );

                    // Unset and additional dimension settings which could disrupt the implied measurements
                    unset($impliedConfigurations[$breakpoint]['file.']['maxW']);
                    unset($impliedConfigurations[$breakpoint]['file.']['maxH']);
                    unset($impliedConfigurations[$breakpoint]['file.']['minW']);
                    unset($impliedConfigurations[$breakpoint]['file.']['minH']);
                    unset($impliedConfigurations[$breakpoint]['file.']['width.']);
                    unset($impliedConfigurations[$breakpoint]['file.']['height.']);

                    // Unsets the "breakpoint/breakpoints" settings.
                    unset($impliedConfigurations[$breakpoint]['file.']['breakpoint']);
                    unset($impliedConfigurations[$breakpoint]['breakpoints']);
                    unset($impliedConfigurations[$breakpoint]['breakpoints.']);
                }

                // Sorts list of image settings in ascending order by breakpoint
                ksort($impliedConfigurations);
                $this->cache->set('impliedConfigurations', $impliedConfigurations);
            }
        }

        return $this->cache->get('impliedConfigurations');
    }


    /*
     * ========================================================
     * Image dimensions
     * ========================================================
     */

    /**
     * Calculates the height for a given width based on the ratio between the default width and height
     *
     * @param string $width
     * @return int|string
     */
    public function modifiedHeight($width)
    {
        $height = floor(intval($width) / intval($this->defaultWidth()) * intval($this->defaultHeight()));
        return preg_replace('/\d+/', $height, $this->defaultHeight());
    }

    /**
     * Gets the height of the default image from file.height of from the img HTML of the default image
     *
     * @return null
     */
    private function defaultHeight()
    {
        if (!$this->cache->has('defaultHeight')) {

            $defaultHeight = false;

            if (isset($this->conf['file.']['height'])) {
                // If set process the configuration in file.height
                $defaultHeight = $this->cObj->stdWrap($this->conf['file.']['height'], $this->conf['file.']['height.']);

            } elseif (preg_match('/height\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Otherwise retreives the default height directly from the default image (unless the image
                // height is defined as a percentage.
                if (is_numeric($match[1])) {
                    $defaultHeight = $match[1];
                }
                // TODO: Get image dimensions from $this->defaultSource(). see view helper functionality
            }

            $this->cache->set('defaultHeight', $defaultHeight);
        }

        return $this->cache->get('defaultHeight');
    }

    /**
     * Calculates the image width for a given breakpoint based on it's ratio to the default breakpoint.
     * Takes into account special settings such as "c" and "m" (i.e. cropping and scaling parameters).
     *
     * @param $breakpoint
     * @return string
     */
    private function modifiedWidth($breakpoint)
    {
        $breakpointWidth = floor(($breakpoint / $this->defaultBreakpoint()) * intval($this->defaultWidth()));
        return preg_replace('/^\d+/', $breakpointWidth, $this->defaultWidth());
    }

    /**
     * Gets the height of the default image from file.width of from the img HTML of the default image
     *
     * @return int|string
     */
    private function defaultWidth()
    {
        if (!$this->cache->has('defaultWidth')) {

            $defaultWidth = false;

            if (isset($this->conf['file.']['width'])) {
                $defaultWidth = $this->cObj->stdWrap($this->conf['file.']['width'], $this->conf['file.']['width.']);

            } elseif (preg_match('/width\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if (is_numeric($match[1])) {
                    $defaultWidth = $match[1];
                }
                // TODO: Get image dimensions from $this->defaultSource()? see view helper functionality
            }

            $this->cache->set('defaultWidth', $defaultWidth);
        }

        return $this->cache->get('defaultWidth');
    }

    /*
     * ========================================================
     * Breakpoints
     * ========================================================
     */

    /**
     * Gets the implied width/height for a given breakpoint.
     *
     *
     * @param $breakpoint
     * @return array
     */
    private function impliedMeasurement($breakpoint)
    {
        $measurements = $this->impliedMeasurements();
        $measurement = array();

        if (is_array($measurements[$breakpoint]) && !empty($measurements[$breakpoint])) {
            $measurement = $measurements[$breakpoint];
        }

        return $measurement;
    }

    /**
     * Constructs the width/height for each of the breakpoints. In most cases this is just a matter of
     * modifying the dimensions of the main image. However, more detailed configuration options are possible
     * and are taken into account.
     *
     * @return array
     */
    private function impliedMeasurements()
    {
        if (!$this->cache->has('impliedMeasurements')) {
            $impliedMeasurements = array();

            // Gets the configuration either from the content element or TypoScript configuration
            if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                $settings = $this->cObj->data['tx_rtpimgquery_breakpoints'];

            } else {
                $settings = $this->conf['breakpoints'];
            }

            // Iterates through all defined breakpoints and constructs their configuration settings
            foreach ($this->breakpoints() as $breakpoint) {

                if ($settings && preg_match('/' . $breakpoint . ':(\w+)/i', $settings, $width)) {
                    // Matches settings like 800:500 where 500 would be the image width for the breakpoint 800
                    $width = $width[1];

                } else {
                    // Gets the implied image width from the breakpoint if no width was defined
                    $width = $this->modifiedWidth($breakpoint);
                }

                // Sets file.width for the current breakpoint
                $impliedMeasurements[$breakpoint]['file.']['width'] = $width;

                // Merges in any other configurations for the breakpoint (e.g. breakpoints.500.x)
                if (isset($this->conf['breakpoints.'][$breakpoint . '.'])) {
                    $impliedMeasurements[$breakpoint]['file.'] =
                        GeneralUtility::array_merge_recursive_overrule(
                            (array)$impliedMeasurements[$breakpoint]['file.'],
                            (array)$this->conf['breakpoints.'][$breakpoint . '.']['file.']
                        );
                }

                // Processes any image height configurations
                $impliedMeasurements[$breakpoint]['file.']['height'] =
                    $this->cObj->stdWrap(
                        $impliedMeasurements[$breakpoint]['file.']['height'],
                        $impliedMeasurements[$breakpoint]['file.']['height.']
                    );

                // Gets the implied height from the width for the current breakpoint if no height was defined.
                if (!$impliedMeasurements[$breakpoint]['file.']['height']) {
                    $impliedMeasurements[$breakpoint]['file.']['height'] =
                        $this->modifiedHeight($impliedMeasurements[$breakpoint]['file.']['width']);
                }
            }

            $this->cache->set('impliedMeasurements', $impliedMeasurements);
        }

        return $this->cache->get('impliedMeasurements');
    }

    /**
     * Gets the default breakpoint from any of the following sources (in order of priority);
     * - As configured in the content element
     * - As configured in TypoScript
     * - The width of the default image.
     *
     * @return int
     */
    private function defaultBreakpoint()
    {
        if (!$this->cache->has('defaultBreakpoint')) {

            if ($this->cObj->data['tx_rtpimgquery_breakpoint']) {
                $defaultBreakpoint = intval($this->cObj->data['tx_rtpimgquery_breakpoint']);

            } elseif ($this->conf['breakpoint']) {
                $defaultBreakpoint = intval($this->conf['breakpoint']);

            } else {
                $defaultBreakpoint = intval($this->defaultWidth());
            }

            $this->cache->set('defaultBreakpoint', $defaultBreakpoint);
        }

        return $this->cache->get('defaultBreakpoint');
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
     * Gets the list of defined breakpoints from the configuration sorted in descending order and
     * including the default breakpoint (i.e. the breakpoint for the default image).
     *
     *
     * @return array
     */
    private function breakpoints()
    {

        if (!$this->cache->has('breakpoints')) {

            $breakpoints = array();

            // The simplest case is that breakpoints are configured as "breakpoints = x, y, z" where
            // x, y & z are the breakpoints and the breakpoints correspond exactly to the image widths. e.g.
            // 400, 600, 1000 would define image widths 400, 600 & 1000 for browser widths 400, 600 & 1000
            // Alternatively the breakpoints can be configure as "breakpoints = x:a, y:b, z:c"
            // where x, y & z are the breakpoints and a, b, c are the image widths. So 400:600 would define an
            // image width of 600 at breakpoint 400.
            if (isset($this->conf['breakpoints']) || $this->cObj->data['tx_rtpimgquery_breakpoints']) {

                if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                    $breakpoints = str_replace(chr(10), ',', $this->cObj->data['tx_rtpimgquery_breakpoints']);

                } else {
                    $breakpoints = $this->conf['breakpoints'];
                }

                // Create an array of breakpoints
                $breakpoints = GeneralUtility::trimExplode(',', $breakpoints, true);

                // Converts something like 610:400 to 610 (we are not interested in image widths)
                $breakpoints = array_filter(array_map('intval', $breakpoints));
            }

            // In addition to or instead of the configuration outlined above, breakpoints can be configured in more
            // detail as "breakpoints.x.file.width = n" where x is the breakpoint n is the corresponding image width.
            if (is_array($this->conf['breakpoints.'])) {

                $configuredBreakpoints = array_filter(array_map('intval', array_keys($this->conf['breakpoints.'])));

                if (is_array($configuredBreakpoints) && !empty($configuredBreakpoints)) {
                    $breakpoints = array_merge((array)$breakpoints, $configuredBreakpoints);
                }
            }

            // Adds the default breakpoint to the list, but only if the list contains other breakpoints (an
            // image configuration with a single breakpoint makes no sense!)
            if (!empty($breakpoints)) {
                $breakpoints[] = $this->defaultBreakpoint();
            }

            // Cleans up and sorts the final list of breakpoints
            $breakpoints = array_map('intval', array_unique($breakpoints));
            sort($breakpoints, SORT_NUMERIC);

            // Caches the breakpoints for the current configuration
            $this->cache->set('breakpoints', $breakpoints);
        }

        // Returns the list of breakpoints
        return $this->cache->get('breakpoints');
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
        if (!$this->cache->has('pixelRatios')) {

            if ($this->cObj->data['tx_rtpimgquery_pixel_ratios']) {
                $pixelRatios = GeneralUtility::trimExplode(',', $this->cObj->data['tx_rtpimgquery_pixel_ratios'], true);

            } else {
                $pixelRatios = GeneralUtility::trimExplode(',', $this->conf['breakpoints.']['pixelRatios'], true);
            }

            // The default device resolution is 1
            array_unshift($pixelRatios, 1);

            // Caches a list of unique values
            $pixelRatios = array_unique(array_map('floatval', $pixelRatios));
            sort($pixelRatios);
            $this->cache->set('pixelRatios', $pixelRatios);
        }

        return $this->cache->get('pixelRatios');
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
        if (!isset($this->layoutContent[$this->layout()])) {
            $this->layoutContent[$this->layout()] = GeneralUtility::getURL($this->layout());
        }

        return $this->layoutContent[$this->layout()];
    }

    /**
     * Gets the responsive image layout
     *
     * @return array
     */
    private function layout()
    {
        if (!isset($this->registry[$this->id]['layout'])) {

            $this->registry[$this->id]['layout'] = GeneralUtility::getFileAbsFileName(self::DEFAULT_LAYOUT);

            if (isset($this->conf['breakpoints.']['layout'])) {
                $layout = GeneralUtility::getFileAbsFileName($this->conf['breakpoints.']['layout']);

                if (is_readable($layout)) {
                    $this->registry[$this->id]['layout'] = $layout;
                }
            }
        }

        return $this->registry[$this->id]['layout'];
    }

    /**
     * # Template Markers
     * Sets list of markers which are inserted into the responsive image layout
     *
     * @return array
     */
    private function markers()
    {
        if (is_null($this->registry[$this->id]['markers'])) {
            $this->registry[$this->id]['markers'] = array(
                '###DEFAULT_IMAGE###' => $this->defaultImage(),
                '###BREAKPOINTS###' => json_encode($this->breakpoints()),
                '###IMAGES###' => json_encode($this->images()),
                '###RATIOS###' => json_encode($this->pixelRatios()),
            );
        }

        return $this->registry[$this->id]['markers'];
    }
}

