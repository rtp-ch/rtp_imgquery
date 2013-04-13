<?php
namespace RTP\RtpImgquery\Xclass;

use \TYPO3\CMS\Frontend\ContentObject\ImageContentObject as ImageContentObject;
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
 *  }
 *
 * @author  Simon Tuck <stu@rtp.ch>
 * @link https://github.com/rtp-ch/rtp_imgquery
 * @todo: Refactor & merge with view helper methods.
 */
class ImageQueryContentObject extends ImageContentObject
{
    /**
     * Registry retains information about generated images
     *
     * @var null
     */
    private $registry;

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
        $this->id = $this->id($conf);

        if ($this->cObj->checkif($conf['if.'])) {

            if ($this->hasDebug()) {
                t3lib_utility_Debug::debugInPopUpWindow(
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
                    t3lib_utility_Debug::debugInPopUpWindow(
                        array(
                            'id' => $this->id,
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
                t3lib_utility_Debug::debugInPopUpWindow(
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
     * Image attributes
     * ========================================================
     */

    /**
     * Gets the style attached to responsive images (the image dimensions should be fluid
     * until it hits the next breakpoint).
     *
     * @return string
     */
    private function style()
    {
        if (!isset($this->registry[$this->id]['style'])) {
            if (isset($this->conf['breakpoints.']['style'])) {
                $style = $this->conf['breakpoints.']['style'];

            } else {
                $style = self::DEFAULT_STYLE;
            }

            // Ensures trailing semicolon in inline style
            if (substr($style, -1) !== ';') {
                $style .= ';';
            }

            $this->registry[$this->id]['style'] = $style;
        }

        return $this->registry[$this->id]['style'];
    }

    /**
     * Checks if default inline styles should be set
     *
     * @return bool
     */
    private function hasStyle()
    {
        static $hasInlineStyle;

        if (is_null($hasInlineStyle)) {

            $hasInlineStyle = true;

            if (isset($this->conf['breakpoints.']['style']) && $this->conf['breakpoints.']['style']) {
                if (preg_match("/^(off|false|no|none|0)$/i", trim($this->conf['breakpoints.']['style']))) {
                    $hasInlineStyle = false;
                }
            }
        }

        return $hasInlineStyle;
    }

    /*
     * ========================================================
     * Images
     * ========================================================
     */

    /**
     * Gets the image tag of the main IMAGE object (i.e. the default image).
     *
     * @return string
     */
    private function defaultImage()
    {
        if (!isset($this->registry[$this->id]['defaultImage'])) {
            $this->registry[$this->id]['defaultImage'] = $this->cObj->cImage($this->conf['file'], $this->conf);
        }

        return $this->registry[$this->id]['defaultImage'];
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
        if (!isset($this->registry[$this->id]['images'])) {

            $this->registry[$this->id]['images'] = array();

            if ($this->hasBreakpoints()) {
                foreach ($this->breakpoints() as $breakpoint) {

                    foreach ($this->pixelRatios() as $pixelRatio) {

                        // Get the implied typoscript configuration for the breakpoint
                        $impliedConfiguration = $this->impliedConfiguration($breakpoint);

                        // TODO: Adjust height/width

                        // Generate the corresponding image with the implied typoscript configuration
                        $image = $this->cObj->cImage($impliedConfiguration['file'], $impliedConfiguration);

                        // If set, handle inline style to make the image fluid (i.e. width/height 100%)
                        if ($this->hasStyle()) {
                            if (preg_match('/style\s*=\s*"([^"]+)"/i', $image)) {
                                // Insert into existing inline style
                                $image = preg_replace(
                                    '%style\s*=\s*"([^"]+)"%i',
                                    ' style="' . $this->style() . ' \1"',
                                    $image
                                );

                            } else {
                                // Or insert new inline style
                                $image = preg_replace('%(\s*/?>$)%im', ' style="' . $this->style() . '"\1', $image);
                            }
                        }

                        // Cache the result
                        $this->registry[$this->id]['images'][$pixelRatio][$breakpoint] = $image;
                    }
                }
            }
        }

        // Return the array of breakpoints/image versions for the current image
        return $this->registry[$this->id]['images'];
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
        return (boolean) $this->conf['breakpoints.']['debug'];
    }

    /**
     * Instance id: a unique Id for each IMAGE object.
     *
     * @param $conf
     * @return string
     */
    private function id($conf)
    {
        return md5(serialize($conf), true);
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
     * Creates and returns a list (sorted in descending order) of breakpoints and their corresponding
     * TypoScript IMAGE configurations.
     *
     * @return array
     */
    private function impliedConfigurations()
    {
        // Creates the TypoScript configuration for each breakpoint image from the TypoScript configuration
        // of the default image and any breakpoint TypoScript configuration.
        if (!isset($this->registry[$this->id]['impliedConfigurations'])) {

            $this->registry[$this->id]['impliedConfigurations'] = array();

            if ($this->hasBreakpoints()) {
                foreach ($this->breakpoints() as $breakpoint) {

                    // Copies the TypoScript configuration of the main IMAGE object to the breakpoint image.
                    $impliedConfigurations[$breakpoint] = $this->conf;

                    // Copies default image TypoScript to each breakpoint (except for the default breakpoint), adjusts
                    // width and height of the breakpoint image version accordingly and applies any breakpoint specific
                    // TypoScript configuration (e.g. breakpoints.x.file.width = n).
                    if ($breakpoint !== $this->defaultBreakpoint()) {

                        // The default settings are overridden by individual breakpoint TypoScript configurations
                        if ($this->hasBreakpointConfiguration($breakpoint)) {
                            $impliedConfigurations[$breakpoint] =
                                GeneralUtility::array_merge_recursive_overrule(
                                    $impliedConfigurations[$breakpoint],
                                    $this->breakpointConfiguration($breakpoint)
                                );
                        }
                    }

                    // Unsets misc. superfluous TypoScript configuration (i.e. breakpoint information
                    // which has been copied to each image)
                    unset($impliedConfigurations[$breakpoint]['file.']['breakpoint']);
                    unset($impliedConfigurations[$breakpoint]['breakpoints']);
                    unset($impliedConfigurations[$breakpoint]['breakpoints.']);
                }

                // Sorts list of image settings descending by breakpoint
                krsort($impliedConfigurations);
                $this->registry[$this->id]['impliedConfigurations'] = $impliedConfigurations;
            }
        }

        return $this->registry[$this->id]['impliedConfigurations'];
    }


    /*
     * ========================================================
     * Image dimensions
     * ========================================================
     */

    /**
     * Modifies the default image height based on the default width and the given breakpoint.
     *
     * @param string $width
     * @return string
     */
    private function modifiedHeight($width)
    {
        $height = floor(intval($width) / intval($this->defaultWidth()) * intval($this->defaultHeight()));
        return preg_replace('/\d+/', $height, $this->defaultHeight());
    }

    /**
     * Gets the height of the default image from file.height taking stdWrap into account
     *
     * @return null
     */
    private function defaultHeight()
    {
        if (!isset($this->registry[$this->id]['defaultHeight'])) {

            $defaultHeight = false;

            if (isset($this->conf['file.']['height'])) {
                $defaultHeight = $this->cObj->stdWrap($this->conf['file.']['height'], $this->conf['file.']['height.']);

            } elseif (preg_match('/height\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if (is_numeric($match[1])) {
                    $defaultHeight = $match[1];
                }
                // TODO: Get image dimensions from $this->defaultSource(). see view helper functionality
            }

            $this->registry[$this->id]['defaultHeight'] = $defaultHeight;
        }

        return $this->registry[$this->id]['defaultHeight'];
    }

    /**
     * Modifies the default image width to match the given breakpoint. Takes into account special
     * settings such as "c" and "m" (i.e. cropping and scaling parameters).
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
     * Gets the width of the default image from file.width taking stdWrap into account
     *
     * @return string
     */
    private function defaultWidth()
    {
        if (!isset($this->registry[$this->id]['defaultWidth'])) {

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

            $this->registry[$this->id]['defaultWidth'] = $defaultWidth;
        }

        return $this->registry[$this->id]['defaultWidth'];
    }

    /*
     * ========================================================
     * Breakpoints
     * ========================================================
     */

    /**
     * Determines if the given breakpoint has TypoScript configuration options.
     *
     * @param $breakpoint
     * @return bool
     */
    private function hasBreakpointConfiguration($breakpoint)
    {
        return (boolean)$this->breakpointConfigurations() && $this->breakpointConfiguration($breakpoint);
    }

    /**
     * Determines if any of the defined breakpoints have configured TypoScript, e.g.
     * breakpoints.x.foo = bar where foo is TypoScript configuration for breakpoint x.
     *
     * @return bool
     */
    private function hasBreakpointConfigurations()
    {
        return (boolean)$this->breakpointConfigurations();
    }

    /**
     * Gets the configured TypoScript for a given breakpoint. For example, retrieves TypoScript for
     * a given breakpoint x from breakpoints.x.foo = bar (foo = bar).
     *
     *
     * @param $breakpoint
     * @return array
     */
    private function breakpointConfiguration($breakpoint)
    {
        $breakpointConfigurations = $this->breakpointConfigurations();

        if (is_array($breakpointConfigurations[$breakpoint]) && !empty($breakpointConfigurations[$breakpoint])) {
            $breakpointConfiguration = $breakpointConfigurations[$breakpoint];

        } else {
            $breakpointConfiguration = null;
        }

        return $breakpointConfiguration;
    }

    /**
     * Gets the configured TypoScript for all breakpoints (breakpoints.[...]).
     *
     * @return array
     */
    private function breakpointConfigurations()
    {
        if (!isset($this->registry[$this->id]['breakpointConfigurations'])) {

            $breakpointConfigurations = array();

            if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                $settings = $this->cObj->data['tx_rtpimgquery_breakpoints'];

            } else {
                $settings = $this->conf['breakpoints'];
            }

            foreach ($this->breakpoints() as $breakpoint) {

                if ($breakpoint !== $this->defaultBreakpoint()) {

                    // Regex matches settings like 800:500 where 500 would be a configured image width
                    // for the breakpoint 800
                    if ($settings && preg_match('/' . $breakpoint . ':(\w+)/i', $settings, $width)) {
                        $breakpointConfigurations[$breakpoint]['file.']['width'] = $width[1];
                    } else {
                        $width = $this->modifiedWidth($breakpoint);
                        $breakpointConfigurations[$breakpoint]['file.']['width'] = $width;
                    }

                    if (isset($this->conf['breakpoints.'][$breakpoint . '.'])) {
                        $breakpointConfigurations[$breakpoint]['file.'] =
                            t3lib_div::array_merge_recursive_overrule(
                                (array)$breakpointConfigurations[$breakpoint]['file.'],
                                (array)$this->conf['breakpoints.'][$breakpoint . '.']['file.']
                            );
                    }

                    // Gets the new height
                    $breakpointConfigurations[$breakpoint]['file.']['height'] =
                        $this->cObj->stdWrap(
                            $breakpointConfigurations[$breakpoint]['file.']['height'],
                            $breakpointConfigurations[$breakpoint]['file.']['height.']
                        );

                    // If no height was defined, gets the height from the new width
                    if (!$breakpointConfigurations[$breakpoint]['file.']['height']) {
                        $breakpointConfigurations[$breakpoint]['file.']['height'] =
                            $this->modifiedHeight($breakpointConfigurations[$breakpoint]['file.']['width']);
                    }

                } else {
                    // TODO: or not...?
                    //$breakpointConfigurations[$breakpoint]['file.']['width'] = $this->defaultWidth();
                }
            }

            $this->registry[$this->id]['breakpointConfigurations'] = $breakpointConfigurations;
        }

        return $this->registry[$this->id]['breakpointConfigurations'];
    }

    /**
     * Gets the default breakpoint either as configured in file.breakpoint or from the width
     * of the default image
     *
     * @return int
     */
    private function defaultBreakpoint()
    {
        if (!isset($this->registry[$this->id]['defaultBreakpoint'])) {

            if ($this->cObj->data['tx_rtpimgquery_breakpoint']) {
                $this->registry[$this->id]['defaultBreakpoint']
                    = intval($this->cObj->data['tx_rtpimgquery_breakpoint']);

            } elseif ($this->conf['breakpoint']) {
                $this->registry[$this->id]['defaultBreakpoint'] = intval($this->conf['breakpoint']);

            } else {
                $this->registry[$this->id]['defaultBreakpoint'] = intval($this->defaultWidth());
            }
        }

        return $this->registry[$this->id]['defaultBreakpoint'];
    }

    /**
     * Determines if breakpoints have been defined
     *
     * @return bool
     */
    private function hasBreakpoints()
    {
        return (boolean) $this->breakpoints();
    }


    /**
     * Gets the list of defined breakpoints sorted in descending order and including the default breakpoint (i.e. the
     * breakpoint for the default image).
     *
     * @return array
     */
    private function breakpoints()
    {

        if (!isset($this->registry[$this->id]['breakpoints'])) {

            $breakpoints = array();

            // The simplest case is that breakpoints are configured as "breakpoints = x, y, z" where
            // x, y & z are the breakpoints and the corresponding image widths. Alternatively the breakpoints can
            // be configure as "breakpoints = x:a, y:b, z:c" where x, y & z are the breakpoints and a, b, c
            // are the image widths.
            if (isset($this->conf['breakpoints']) || $this->cObj->data['tx_rtpimgquery_breakpoints']) {
                if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                    $breakpoints = str_replace(chr(10), ',', $this->cObj->data['tx_rtpimgquery_breakpoints']);
                } else {
                    $breakpoints = $this->conf['breakpoints'];
                }
                $breakpoints = GeneralUtility::trimExplode(',', $breakpoints, true);

                // Converts something like 610:400 to 610
                $breakpoints = array_filter(array_map('intval', $breakpoints));
            }

            // A more detailed configuration is breakpoints.x.file.width = n where x is the breakpoint
            // (i.e. viewport width) and n is the corresponding image width.
            if (is_array($this->conf['breakpoints.'])) {
                $configuredBreakpoints = array_filter(array_map('intval', array_keys($this->conf['breakpoints.'])));
                if (is_array($configuredBreakpoints) && !empty($configuredBreakpoints)) {
                    $breakpoints = array_merge($breakpoints, $configuredBreakpoints);
                }
            }

            // If breakpoints have been defined or if a breakpoint has explicitly been set for the default image (i.e.
            // it's possible to define an image which has a breakpoint, but no alternative images!):
            // Adds the breakpoint of the default image from file.breakpoint = x (if undefined the breakpoint is
            // assumed to be the width of the default image). Also sorts the list of breakpoints in descending order.
            // TODO: Does this make sense? could be used to implement breakpoint behaviour with just 1 image...
            if (!empty($breakpoints) ||
                isset($this->conf['file.']['breakpoint']) ||
                $this->cObj->data['tx_rtpimgquery_breakpoints']
            ) {

                $breakpoints[] = $this->defaultBreakpoint();
                $breakpoints = array_map('intval', array_unique($breakpoints));
                rsort($breakpoints, SORT_NUMERIC);
            }

            $this->registry[$this->id]['breakpoints'] = $breakpoints;
        }

        return $this->registry[$this->id]['breakpoints'];
    }


    /*
     * =======================================================
     * Retina Images
     * =======================================================
     */


    /**
     * Checks if the retina image feature is enabled.
     *
     * @return bool
     */
    private function hasPixelRatios()
    {
        return (boolean) $this->conf['enablePixelRatios'];
    }

    /**
     * Returns an array of configured retina ratios to be used for image generation.
     *
     * @return array list of the configured retina ratios, will always include the default ratio "1"
     */
    private function pixelRatios()
    {
        $pixelRatios = array();

        if ($this->hasPixelRatios()) {

            if ($this->cObj->data['tx_rtpimgquery_pixel_ratios']) {
                $pixelRatios = GeneralUtility::trimExplode(',', $this->cObj->data['tx_rtpimgquery_pixel_ratios'], true);

            } else {
                $pixelRatios = GeneralUtility::trimExplode(',', $this->conf['pixelRatios'], true);
            }
        }

        // The default device resolution is 1
        array_unshift($pixelRatios, 1);

        // Returns a list of unique values
        return array_unique(array_map('floatval', $pixelRatios));
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
                '###DEFAULT_IMAGE###' => $this->image($this->defaultBreakpoint()),
                '###BREAKPOINTS###' => json_encode($this->breakpoints()),
                '###IMAGES###' => json_encode($this->images()),
                '###RATIOS###' => json_encode($this->ratios()),
            );
        }

        return $this->registry[$this->id]['markers'];
    }
}

