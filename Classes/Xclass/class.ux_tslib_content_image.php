<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Simon Tuck <stu@rtp.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Extends IMAGE class object for responsive images
 *
 * @author Simon Tuck <stu@rtp.ch>
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
     * Default template
     *
     * @var string
     */
    const DEFAULT_TEMPLATE              = 'EXT:rtp_imgquery/Resources/Private/rtp_imgquery.min.html';

    /**
     * Initial content of responsive images templates
     *
     * @var null
     */
    private $templateContent            = null;

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

        if ($this->cObj->checkIf($conf['if.'])) {
/*
t3lib_utility_Debug::debugInPopUpWindow(array(
    'conf'              => $this->conf,
    'hasBreakpoints'    => $this->hasBreakpoints()
));
*/

            // If breakpoints have been defined in the TypoScript configuration create
            // a responsive version of the image
            if( $this->hasBreakpoints() ) {
                $theValue = $this->responsiveImage();
/*
//
t3lib_utility_Debug::debugInPopUpWindow(array(
    'id'                => $this->id(),
    'info'              => $this->cObj->getImgResource($this->conf['file'], $this->conf),
    'conf'              => $this->conf,
    'impliedConf'       => $this->impliedConfigurations(),
    'defaultImage'      => $this->defaultImage(),
    'defaultWidth'      => $this->defaultWidth(),
    'defaultBreakpoint' => $this->defaultBreakpoint(),
    'breakpoints'       => $this->breakpoints(),
    'images'            => $this->images(),
    'attributes'        => $this->attributes(),
    'markers'           => $this->getMarkers(),
    'theValue'          => $theValue,
));
*/

            // Otherwise create the default image
            } else {
                $theValue = $this->defaultImage();
            }

            if (isset($conf['stdWrap.'])) {
                $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
            }

            return $theValue;
        }
    }

    /**
     * Parses and returns the responsive image template content unless all breakpoints point to the
     * same image. In which case the default image is returned (sans responsiveness).
     *
     * @return string
     */
    private function responsiveImage()
    {

        $this->setMarkers();
        $this->cleanMarkers();

        if(count($this->breakpoints()) > 1) {
            $search     = array_keys($this->getMarkers());
            $replace    = $this->getMarkers();
            $content    = $this->templateContent();
            return html_entity_decode(str_ireplace($search, $replace, $content));
        } else {
            return $this->defaultImage();
        }
    }

    /**
     * Gets the initial content of the current responsive image template
     *
     * @return string
     */
    private function templateContent()
    {
        if( !isset($this->templateContent[$this->template()]) ) {
            $this->templateContent[$this->template()] = t3lib_div::getURL($this->template());
        }
        return $this->templateContent[$this->template()];
    }

    /**
     * Gets the responsive image template
     *
     * @return array
     */
    private function template()
    {
        if( !isset($this->registry[$this->id]['template']) ) {
            $this->registry[$this->id]['template'] = t3lib_div::getFileAbsFileName(self::DEFAULT_TEMPLATE);
            if(isset($this->conf['breakpoints.']['template'])) {
                $template = t3lib_div::getFileAbsFileName($this->conf['breakpoints.']['template']);
                if( is_readable($template) ) {
                    $this->registry[$this->id]['template'] = $template;
                }
            }
        }
        return $this->registry[$this->id]['template'];
    }

    /**
     * Sets list of markers which are inserted into the responsive image template
     *
     * @return array
     */
    private function setMarkers()
    {
        $this->registry[$this->id]['markers'] = array(
            '###DEFAULT_IMAGE###'       => $this->defaultImage(),
            '###DEFAULT_WIDTH###'       => $this->defaultWidth(),
            '###DEFAULT_BREAKPOINT###'  => $this->defaultBreakpoint(),
            '###BREAKPOINTS###'         => json_encode($this->breakpoints()),
            '###IMAGES###'              => json_encode($this->images()),
            '###ATTRIBUTES###'          => json_encode($this->attributes()),
            '###ID###'                  => json_encode($this->id())
        );
    }

    /**
     * Gets the marker array which is inserted into the responsive image template
     *
     * @return array
     */
    private function getMarkers()
    {
        return $this->registry[$this->id]['markers'];
    }

    /**
     * Cleans the marker array by removing duplicates. For example, if certain breakpoint/image combinations
     * are identical they will be removed.
     *
     * @return void
     */
    private function cleanMarkers()
    {
        $hasDuplicates = array_keys(array_diff_key($this->images(), array_unique($this->images())));
        if(!empty($hasDuplicates)) {
            foreach($hasDuplicates as $duplicate) {
                $this->removeBreakpoint($duplicate);
            }
            $this->setMarkers();
        }
    }

    /**
     * Utility method to reset the marker array
     *
     * @return void
     */
    private function resetMarkers()
    {
        if( isset($this->registry[$this->id]['markers'])) {
            unset($this->registry[$this->id]['markers']);
            $this->markers();
        }
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
     * Gets info on the original image, e.g. dimensions, file hash, etc.
     *
     * @return array
     */
    private function imageInfo()
    {
        if( !isset($this->registry[$this->id]['imageInfo']) ) {
            $this->registry[$this->id]['imageInfo'] = $this->cObj->getImgResource($this->conf['file'], $this->conf);
        }
        return $this->registry[$this->id]['imageInfo'];
    }

    /**
     * Image id: IMAGE objects where the exact same TypoScript configuration are identical (the
     * id is created from the initial TypoScript configuration array).
     *
     * @return null
     */
    private function id()
    {
        return md5(serialize($this->conf));
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
                    $this->registry[$this->id]['images'][$breakpoint] = $this->cObj->cImage($impliedConfiguration['file'], $impliedConfiguration);
                }
            }
        }
        return $this->registry[$this->id]['images'];
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
                    if($breakpoint !== $this->defaultBreakpoint()) {

                        // By default width and height are based on the breakpoint
                        $impliedConfigurations[$breakpoint]['file.']['width'] = $this->modifiedWidth($breakpoint);
                        $impliedConfigurations[$breakpoint]['file.']['height'] = $this->modifiedHeight($breakpoint);

                        // The default settings are overridden by individual breakpoint TypoScript configuration
                        if($this->hasBreakpointConfiguration($breakpoint)) {
                            $impliedConfigurations[$breakpoint] = array_merge($impliedConfigurations[$breakpoint], $this->breakpointConfiguration($breakpoint));
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

    /**
     * Modifies the default image height based on the default width and the given breakpoint.
     *
     * @param $breakpoint
     * @return string
     */
    private function modifiedHeight($breakpoint)
    {
        $height = floor($breakpoint / intval($this->defaultWidth()) * intval($this->defaultHeight()));
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
            $this->registry[$this->id]['defaultHeight'] = $this->cObj->stdWrap($this->conf['file.']['height'], $this->conf['file.']['height.']);
        }
        return $this->registry[$this->id]['defaultHeight'];
    }

    /**
     * Gets the height of the original image
     *
     * @return int
     */
    private function originalHeight()
    {
        $info = $this->imageInfo();
        return $info[1];
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
        return preg_replace('/\d+/', $breakpoint, $this->defaultWidth());
    }

    /**
     * Gets the width of the default image from file.width taking stdWrap into account
     *
     * @return string
     */
    private function defaultWidth()
    {
        if( !isset($this->registry[$this->id]['defaultWidth']) ) {
            $this->registry[$this->id]['defaultWidth'] = $this->cObj->stdWrap($this->conf['file.']['width'], $this->conf['file.']['width.']);
        }
        return $this->registry[$this->id]['defaultWidth'];
    }

    /**
     * Gets the width of the original image
     *
     * @return int
     */
    private function originalWidth()
    {
        $info = $this->imageInfo();
        return $info[0];
    }

    /**
     * Determines if a default breakpoint has __explicitly__ been set, i.e. if there is a defined breakpoint for
     * the default image (file.breakpoint in the TypoScript configuration)
     *
     * @return bool
     */
    private function hasDefaultBreakpoint()
    {
        return isset($this->conf['file.']['breakpoint']);
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
            $this->registry[$this->id]['defaultBreakpoint'] =  $this->conf['file.']['breakpoint'] ? intval($this->conf['file.']['breakpoint']) : intval($this->defaultWidth());

        }
        return $this->registry[$this->id]['defaultBreakpoint'];
    }

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
        if(is_array($breakpointConfigurations[$breakpoint . '.']) && !empty($breakpointConfigurations[$breakpoint . '.'])) {
            $breakpointConfiguration = $breakpointConfigurations[$breakpoint . '.'];
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
            $this->registry[$this->id]['breakpointConfigurations'] = array();
            if(is_array($this->conf['breakpoints.']) && !empty($this->conf['breakpoints.'])) {
                foreach($this->conf['breakpoints.'] as $breakpoint => $breakpointConfiguration) {
                    if(is_numeric(substr($breakpoint, 0, -1))) {
                        $this->registry[$this->id]['breakpointConfigurations'][$breakpoint] = $breakpointConfiguration;
                    }
                }
            }
        }
        return $this->registry[$this->id]['breakpointConfigurations'];
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

            // The simplest (and most unlikely) case is that breakpoints are configured as
            // breakpoints = x, y, z where x, y & z are the breakpoints (i.e. viewport width)
            // and the image widths.
            if( isset($this->conf['breakpoints']) ) {
                $breakpoints = t3lib_div::trimExplode(',', $this->conf['breakpoints'], true);
            }

            // A more detailed (and plausible) configuration is breakpoints.x.file.width = n where x
            // is the breakpoint (i.e. viewport width) and n is the corresponding image width.
            if( $this->hasBreakpointConfigurations() ) {
                $breakpoints = array_merge($breakpoints, (array) array_map('intval', array_keys($this->breakpointConfigurations())));
            }

            // If breakpoints have been defined or if a breakpoint has explicitly been set for the default image (i.e.
            // it's possible to define an image which has a breakpoint, but no alternative images!):
            // Adds the breakpoint of the default image from file.breakpoint = x (if undefined the breakpoint is
            // assumed to be the width of the default image). Also sorts the list of breakpoints in descending order.
            if(!empty($breakpoints) || $this->hasDefaultBreakpoint()) {
                $breakpoints[] = $this->defaultBreakpoint();
                $breakpoints = array_map('intval', array_unique($breakpoints));
                rsort($breakpoints, SORT_NUMERIC);
            }

            $this->registry[$this->id]['breakpoints'] = $breakpoints;
        }

        return $this->registry[$this->id]['breakpoints'];
    }

    /**
     * Removes a breakpoint from the responsive image
     *
     * @param $breakpoint
     */
    private function removeBreakpoint($breakpoint)
    {
        $index = array_search($breakpoint, $this->registry[$this->id]['breakpoints']);
        unset($this->registry[$this->id]['breakpoints'][$index]);
        unset($this->registry[$this->id]['images'][$breakpoint]);
        unset($this->registry[$this->id]['attributes'][$breakpoint]);
    }

    /**
     * Determines if images can be scaled up. If not then certain image widths will not make sense.
     *
     * @return bool
     */
    private function hasNoScaleUp()
    {
        return (boolean) $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'];
    }
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:rtp_imgquery/Classes/Xclass/class.ux_tslib_content_image.php'])) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:rtp_imgquery/Classes/Xclass/class.ux_tslib_content_image.php']);
}
