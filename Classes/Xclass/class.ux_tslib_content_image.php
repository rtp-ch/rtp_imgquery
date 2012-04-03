<?php

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
class ux_tslib_content_Image extends tslib_content_Image
{
    /**
     * Registry retains information about generated images
     *
     * @var null
     */
    private $registry                   = null;

    /**
     * Default layout
     *
     * @var string
     */
    const DEFAULT_LAYOUT                = 'EXT:rtp_imgquery/Resources/Private/Templates/rtp_imgquery.min.html';

    /**
     * Default style for responsive images
     *
     * @var string
     */
    const DEFAULT_STYLE                 = 'width: 100%; height: auto';

    /**
     * Initial content of responsive images layouts
     *
     * @var null
     */
    private $layoutContent              = null;

    /**
     * TypoScript configuration
     *
     * @var array
     */
    private $conf                       = null;

    /**
     * Image id
     *
     * @var string
     */
    private $id                         = null;

    /**
     * Rendering the cObject, IMAGE
     *
     * @param    array        Array of TypoScript properties
     * @return    string        Output
     */
    public function render($conf = array())
    {
        // Initialize the IMAGE object. Note that "tslib_content_Image" is implicitly implemented as a singleton
        // so a unique id is required to differentiate between the various IMAGE objects and the $registry variable
        // is used to store the details of each individual IMAGE object.
        $this->conf = $conf;
        $this->id = $this->id();

        if( $this->cObj->checkIf($conf['if.']) ) {

if($this->hasDebug()) {
    t3lib_utility_Debug::debugInPopUpWindow(array(
        '================'              => '================',
        'conf'                          => $this->conf,
        'hasBreakpoints'                => $this->hasBreakpoints(),
        'tx_rtpimgquery_breakpoints'    => $this->cObj->data['tx_rtpimgquery_breakpoints'],
        'tx_rtpimgquery_breakpoint'     => $this->cObj->data['tx_rtpimgquery_breakpoint'],
    ));
}

            // If breakpoints have been defined in the TypoScript configuration create
            // a responsive version of the image
            if( $this->hasBreakpoints() ) {
                $imageHtml = $this->responsiveImage();

if($this->hasDebug()) {
    t3lib_utility_Debug::debugInPopUpWindow(array(
        'id'                => $this->id(),
        'defaultImage'      => $this->defaultImage(),
        'defaultWidth'      => $this->defaultWidth(),
        'breakpoints'       => $this->breakpoints(),
        'defaultBreakpoint' => $this->defaultBreakpoint(),
        'impliedConf'       => $this->impliedConfigurations(),
        'images'            => $this->images(),
        'attributes'        => $this->attributes(),
        'markers'           => $this->markers()
    ));
}

            // Otherwise create the default image
            } else {
                $imageHtml = $this->defaultImage();
            }

            if( isset($conf['stdWrap.']) ) {
                $imageHtml = $this->cObj->stdWrap($imageHtml, $conf['stdWrap.']);
            }

if($this->hasDebug()) {
    t3lib_utility_Debug::debugInPopUpWindow(array(
        'imageHtml'         => $imageHtml,
        '================'  => '================',
    ));
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
        if(count($this->breakpoints()) > 1) {
            $search     = array_keys($this->markers());
            $replace    = $this->markers();
            $content    = $this->layoutContent();
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
     * TODO: Should also be possible to unset style, i.e. to not apply the default style
     * @return string
     */
    private function style()
    {
        if (!isset($this->registry[$this->id]['style']) ) {
            if (isset($this->conf['breakpoints.']['style']) ) {
                $style = $this->conf['breakpoints.']['style'];
            } else {
                $style = self::DEFAULT_STYLE;
            }
            if( substr($style, -1) !== ';' ) {
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
        $hasInlineStyle = true;
        if (isset($this->conf['breakpoints.']['style'])) {
            if (preg_match("/^(off|false|no|none|0)$/i", trim($this->conf['breakpoints.']['style']))) {
                $hasInlineStyle = false;
            }
        }
        return $hasInlineStyle;
    }

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
        if(is_array($attributes[$breakpoint]) && !empty($attributes[$breakpoint])) {
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
        if( !isset($this->registry[$this->id]['attributes']) ) {
            $this->registry[$this->id]['attributes'] = array();
            foreach ($this->images() as $breakpoint => $image) {
                // http://stackoverflow.com/questions/317053/regular-expression-for-extracting-tag-attributes
                if(preg_match_all('/(\S+)=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/s', $image, $attributes)) {
                    $this->registry[$this->id]['attributes'][$breakpoint] =  array_combine($attributes[1], $attributes[2]);
                }
            }
        }
        return $this->registry[$this->id]['attributes'];
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
        if( !isset($this->registry[$this->id]['defaultImage']) ) {
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
        if(is_string($images[$breakpoint])) {
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
        if( !isset($this->registry[$this->id]['images']) ) {
            $this->registry[$this->id]['images'] = array();
            if($this->hasBreakpoints()) {
                foreach($this->breakpoints() as $breakpoint) {
                    $impliedConfiguration = $this->impliedConfiguration($breakpoint);
                    $image = $this->cObj->cImage($impliedConfiguration['file'], $impliedConfiguration);
                    // Inserts image styles
                    if ($this->hasStyle()) {
                        if (preg_match('/style\s*=\s*"([^"]+)"/i', $image)) {
                            $image = preg_replace('%style\s*=\s*"([^"]+)"%i', ' style="' . $this->style() . ' \1"', $image);
                        } else {
                            $image = preg_replace('%(\s*/?>$)%im', ' style="' . $this->style() . '"\1', $image);
                        }
                    }
                    $this->registry[$this->id]['images'][$breakpoint] = $image;
                }
            }
        }
        return $this->registry[$this->id]['images'];
    }

    /*
     * ========================================================
     * Configuration
     * ========================================================
     */

    private function hasDebug()
    {
        return (boolean) $this->conf['breakpoints.']['debug'];
    }

    /**
     * Instance id: a unique Id for each IMAGE object.
     *
     * @return string
     */
    private function id()
    {
        return md5(uniqid(mt_rand(), true));
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
        if(is_array($impliedConfigurations[$breakpoint]) && !empty($impliedConfigurations[$breakpoint])) {
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
        if( !isset($this->registry[$this->id]['impliedConfigurations']) ) {

            $this->registry[$this->id]['impliedConfigurations'] = array();

            if($this->hasBreakpoints()) {
                foreach($this->breakpoints() as $breakpoint) {

                    // Copies the TypoScript configuration of the main IMAGE object to the breakpoint image.
                    $impliedConfigurations[$breakpoint] = $this->conf;

                    // Copies default image TypoScript to each breakpoint (except for the default breakpoint), adjusts
                    // width and height of the breakpoint image version accordingly and applies any breakpoint specific
                    // TypoScript configuration (e.g. breakpoints.x.file.width = n).
                    if( $breakpoint !== $this->defaultBreakpoint() ) {

                        // The default settings are overridden by individual breakpoint TypoScript configurations
                        if( $this->hasBreakpointConfiguration($breakpoint) ) {
                            $impliedConfigurations[$breakpoint] =
                                    t3lib_div::array_merge_recursive_overrule($impliedConfigurations[$breakpoint], $this->breakpointConfiguration($breakpoint));
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
        if( !isset($this->registry[$this->id]['defaultHeight']) ) {
            if( isset($this->conf['file.']['height']) ) {
                $defaultHeight = $this->cObj->stdWrap($this->conf['file.']['height'], $this->conf['file.']['height.']);
            } elseif(preg_match('/height\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if( is_numeric($match[1]) ) {
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
        if( !isset($this->registry[$this->id]['defaultWidth']) ) {
            if( isset($this->conf['file.']['width']) ) {
                $defaultWidth = $this->cObj->stdWrap($this->conf['file.']['width'], $this->conf['file.']['width.']);
            } elseif(preg_match('/width\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if( is_numeric($match[1]) ) {
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
        return (boolean) $this->breakpointConfigurations() && $this->breakpointConfiguration($breakpoint);
    }

    /**
     * Determines if any of the defined breakpoints have configured TypoScript, e.g.
     * breakpoints.x.foo = bar where foo is TypoScript configuration for breakpoint x.
     *
     * @return bool
     */
    private function hasBreakpointConfigurations()
    {
        return (boolean) $this->breakpointConfigurations();
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
        if( !isset($this->registry[$this->id]['breakpointConfigurations']) ) {

            $breakpointConfigurations = array();

            if( $this->cObj->data['tx_rtpimgquery_breakpoints'] ) {
                $settings = $this->cObj->data['tx_rtpimgquery_breakpoints'];
            } else {
                $settings = $this->conf['breakpoints'];
            }

            foreach($this->breakpoints() as $breakpoint) {

                if( $breakpoint !== $this->defaultBreakpoint() ) {

                    // Regex matches settings like 800:500 where 500 would be a configured image width
                    // for the breakpoint 800
                    if($settings && preg_match('/' . $breakpoint . ':(\w+)/i', $settings, $width)) {
                        $breakpointConfigurations[$breakpoint]['file.']['width'] = $width[1];
                    } else {
                        $width = $this->modifiedWidth($breakpoint);
                        $breakpointConfigurations[$breakpoint]['file.']['width'] = $width;
                    }

                    if( isset($this->conf['breakpoints.'][$breakpoint . '.']) ) {
                        $breakpointConfigurations[$breakpoint]['file.'] =
                            t3lib_div::array_merge_recursive_overrule((array) $breakpointConfigurations[$breakpoint]['file.'],
                                                                      (array) $this->conf['breakpoints.'][$breakpoint . '.']['file.']);
                    }

                    // Gets the new height
                    $breakpointConfigurations[$breakpoint]['file.']['height'] =
                                        $this->cObj->stdWrap($breakpointConfigurations[$breakpoint]['file.']['height'],
                                                             $breakpointConfigurations[$breakpoint]['file.']['height.']);

                    // If no height was defined, gets the height from the new width
                    if(!$breakpointConfigurations[$breakpoint]['file.']['height']) {
                        $breakpointConfigurations[$breakpoint]['file.']['height'] =
                                        $this->modifiedHeight($breakpointConfigurations[$breakpoint]['file.']['width']);
                    }

                } else {
                    // TODO: or not to do...?
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
        if( !isset($this->registry[$this->id]['defaultBreakpoint']) ) {

            if( $this->cObj->data['tx_rtpimgquery_breakpoint'] ) {
                $this->registry[$this->id]['defaultBreakpoint'] = intval($this->cObj->data['tx_rtpimgquery_breakpoint']);
            } elseif( $this->conf['breakpoint'] ) {
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

        if( !isset($this->registry[$this->id]['breakpoints']) ) {

            $breakpoints = array();

            // The simplest case is that breakpoints are configured as "breakpoints = x, y, z" where
            // x, y & z are the breakpoints and the corresponding image widths. Alternatively the breakpoints can
            // be configure as "breakpoints = x:a, y:b, z:c" where x, y & z are the breakpoints and a, b, c
            // are the image widths.
            if( isset($this->conf['breakpoints'])  || $this->cObj->data['tx_rtpimgquery_breakpoints'] ) {
                if( $this->cObj->data['tx_rtpimgquery_breakpoints'] ) {
                    $breakpoints = str_replace(chr(10), ',', $this->cObj->data['tx_rtpimgquery_breakpoints']);
                } else {
                    $breakpoints = $this->conf['breakpoints'];
                }
                $breakpoints = t3lib_div::trimExplode(',', $breakpoints, true);

                // Converts something like 610:400 to 610
                $breakpoints = array_filter(array_map('intval', $breakpoints));
            }

            // A more detailed configuration is breakpoints.x.file.width = n where x is the breakpoint
            // (i.e. viewport width) and n is the corresponding image width.
            if( is_array($this->conf['breakpoints.']) ){
                $configuredBreakpoints = array_filter(array_map('intval', array_keys($this->conf['breakpoints.'])));
                if( is_array($configuredBreakpoints) && !empty($configuredBreakpoints) ) {
                    $breakpoints = array_merge($breakpoints, $configuredBreakpoints);
                }
            }

            // If breakpoints have been defined or if a breakpoint has explicitly been set for the default image (i.e.
            // it's possible to define an image which has a breakpoint, but no alternative images!):
            // Adds the breakpoint of the default image from file.breakpoint = x (if undefined the breakpoint is
            // assumed to be the width of the default image). Also sorts the list of breakpoints in descending order.
            // TODO: Sanity check, does this make sense: could be used to implement breakpoint behaviour with just 1 image...
            if( !empty($breakpoints) || isset($this->conf['file.']['breakpoint']) || $this->cObj->data['tx_rtpimgquery_breakpoints'] ) {
                $breakpoints[] = $this->defaultBreakpoint();
                $breakpoints = array_map('intval', array_unique($breakpoints));
                rsort($breakpoints, SORT_NUMERIC);
            }

            $this->registry[$this->id]['breakpoints'] = $breakpoints;
        }

        return $this->registry[$this->id]['breakpoints'];
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
        if( !isset($this->layoutContent[$this->layout()]) ) {
            $this->layoutContent[$this->layout()] = t3lib_div::getURL($this->layout());
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
        if( !isset($this->registry[$this->id]['layout']) ) {
            $this->registry[$this->id]['layout'] = t3lib_div::getFileAbsFileName(self::DEFAULT_LAYOUT);
            if(isset($this->conf['breakpoints.']['layout'])) {
                $layout = t3lib_div::getFileAbsFileName($this->conf['breakpoints.']['layout']);
                if( is_readable($layout) ) {
                    $this->registry[$this->id]['layout'] = $layout;
                }
            }
        }
        return $this->registry[$this->id]['layout'];
    }

    /**
     * Sets list of markers which are inserted into the responsive image layout
     *
     * @return array
     */
    private function markers()
    {
        if( is_null($this->registry[$this->id]['markers']) ) {
            $this->registry[$this->id]['markers'] = array(
                '###DEFAULT_IMAGE###'       => $this->image($this->defaultBreakpoint()),
                '###DEFAULT_WIDTH###'       => $this->defaultWidth(),
                '###DEFAULT_BREAKPOINT###'  => $this->defaultBreakpoint(),
                '###BREAKPOINTS###'         => json_encode($this->breakpoints()),
                '###IMAGES###'              => json_encode($this->images()),
                '###ATTRIBUTES###'          => json_encode($this->attributes()),
                '###ID###'                  => json_encode($this->id())
            );
        }
        return $this->registry[$this->id]['markers'];
    }
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:rtp_imgquery/Classes/Xclass/class.ux_tslib_content_image.php'])) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:rtp_imgquery/Classes/Xclass/class.ux_tslib_content_image.php']);
}
