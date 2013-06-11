<?php
namespace RTP\RtpImgquery\Xclass;

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
     * Default layout
     *
     * @var string
     */
    const DEFAULT_STRATEGY = 'EXT:rtp_imgquery/Resources/Private/Templates/Build/html/imgQuery.html';

    /**
     * Default style for responsive images
     *
     * @var string
     */
    const DEFAULT_STYLE = 'width: 100%; height: auto';

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
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    private $impliedMeasurements;

    private $fluidStyle;

    private $impliedConfigurations;

    private $defaultHeight;

    private $defaultWidth;

    private $defaultBreakpoint;

    private $breakpoints;

    private $pixelRatios;

    private $strategy;

    private $generatedImages;

    private $markers;

    private $cacheKey;

    /**
     * Rendering the cObject, IMAGE
     *
     * @param    array        Array of TypoScript properties
     * @return    string        Output
     */
    public function render($conf = array())
    {
        $this->conf = $conf;

        if ($this->cObj->checkif($this->conf['if.'])) {

            $this->setDefaultImage();
            $this->setPixelRatios();
            $this->setDefaultBreakpoint();
            $this->setBreakpoints();

            // Creates responsive images if breakpoints have been defined
            if ($this->hasDefaultBreakpoint() && $this->hasBreakpoints()) {

                $this->setDefaultWidth();
                $this->setDefaultHeight();

                $this->setImpliedMeasurements();
                $this->setImpliedConfigurations();

                $this->setFluidStyle();
                $this->setGeneratedImages();

                $this->setStrategy();
                $this->setCacheKey();
                $this->setMarkers();

                $imageHtml = html_entity_decode(
                    str_ireplace(
                        array_keys($this->getMarkers()),
                        $this->getMarkers(),
                        $this->getStrategy()
                    )
                );

            } else {
                // Otherwise create the default image
                $imageHtml = $this->getDefaultImage();
            }

            if (isset($conf['stdWrap.'])) {
                $imageHtml = $this->cObj->stdWrap($imageHtml, $conf['stdWrap.']);
            }

            return $imageHtml;
        }
    }


    /*
     * ========================================================
     * Image Style
     * ========================================================
     */

    /**
     * Implements inline style for a given HTML img tag.
     *
     * @param $image
     * @return mixed
     */
    private function insertFluidStyle($image)
    {
        if (preg_match('%<img[^>]+style\s*=\s*"[^"]+"[^>]*/?>%i', $image)) {
            // Augment an existing inline style
            $search = '%<img([^>]+)style\s*=\s*"([^"]+)"([^>]*)(/?>)%i';
            $replace = '<img$1style="$2;' . $this->getFluidStyle() . '"$3$4';
            $image = preg_replace($search, $replace, $image);

        } else {

            // Or insert new inline style
            $image = preg_replace('%<img([^>]+)(/?>)%i', '<img style="' . $this->getFluidStyle() . '"$1$2', $image);
        }

        return $image;
    }

    /**
     *
     */
    private function setFluidStyle()
    {
        $this->fluidStyle = false;
        $extConf = (array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rtp_imgquery']);

        // Fluid image style has to be enabled globally
        if ($extConf['enableFluidImages']) {

            // The style has to be defined in the TypoScript configuration
            $style = $this->conf['breakpoints.']['style'];

            // If it's enabled it can be disabled on a case-by-case basis by setting
            // a falsy or empty value
            if ($style && !preg_match("/^(off|false|no|none|0)$/i", $style)) {

                // Sets fluidStyle from the configuration
                $this->fluidStyle = $style;

                // Ensures trailing semicolon in inline style
                if (substr($this->fluidStyle, -1) !== ';') {
                    $this->fluidStyle .= ';';
                }
            }
        }
    }

    /**
     * Gets the style attached to responsive images (the image dimensions should be fluid
     * until it hits the next breakpoint).
     *
     * @return string
     */
    private function getFluidStyle()
    {
        return $this->fluidStyle;
    }

    /**
     * Checks if default inline styles should be applied
     *
     * @return bool
     */
    private function hasFluidStyle()
    {
        return (boolean) $this->getFluidStyle();
    }


    /*
     * ========================================================
     * Images
     * ========================================================
     */

    /**
     * Gets the img HTML for the default image.
     *
     * @return string
     */
    private function getDefaultImage()
    {
        return $this->defaultImage;
    }

    /**
     *
     */
    private function setDefaultImage()
    {
        $this->defaultImage = $this->cObj->cImage($this->conf['file'], $this->conf);
    }

    /**
     * Creates the images for all breakpoints and returns a list of final image tags per breakpoint.
     *
     * @return array
     */
    private function setGeneratedImages()
    {
        $this->generatedImages = array();

        // Generates images according to their implied configurations by device pixel ratio and breakpoint
        if ($this->hasBreakpoints()) {
            foreach ($this->getPixelRatios() as $pixelRatio) {
                foreach ($this->getBreakpoints() as $breakpoint) {

                    // Get the implied typoscript configuration for the breakpoint
                    $impliedConfiguration = $this->getImpliedConfigurations($breakpoint);

                    if ($pixelRatio > 1) {
                        $standardWidth = $impliedConfiguration['file.']['width'];
                        $impliedConfiguration['file.']['width'] = $pixelRatio * $standardWidth;

                        $standardHeight = $impliedConfiguration['file.']['height'];
                        $impliedConfiguration['file.']['height'] = $pixelRatio * $standardHeight;
                    }

                    // Generate the corresponding image with the implied typoscript configuration
                    $image = $this->cObj->cImage($impliedConfiguration['file'], $impliedConfiguration);

                    // Implements inline styles
                    // If set, handle inline style to make the image fluid (i.e. width/height 100%)
                    if ($this->hasFluidStyle()) {
                        $image = $this->insertFluidStyle($image);
                    }

                    //
                    $this->generatedImages[strval($pixelRatio)][strval($breakpoint)] = $image;
                }
            }
        }
    }

    /**
     * @return mixed
     */
    private function getGeneratedImages()
    {
        return $this->generatedImages;
    }

    /**
     * @return bool
     */
    private function hasGeneratedImages()
    {
        return (boolean) $this->getGeneratedImages();
    }


    /*
     * ========================================================
     * Breakpoint Configurations
     * ========================================================
     */

    /**
     * Gets the implied width/height for a given breakpoint.
     *
     * @param $breakpoint
     * @return array
     */
    private function getImpliedMeasurements($breakpoint = null)
    {
        $measurements = $this->impliedMeasurements;

        if (!is_null($breakpoint)) {
            $measurements = $measurements[$breakpoint];
        }

        return $measurements;
    }

    /**
     * Constructs the width/height for each of the breakpoints. In most cases this is just a matter of
     * modifying the dimensions of the main image. However, more detailed configuration options are possible
     * and are taken into account.
     *
     * @return array
     */
    private function setImpliedMeasurements()
    {
        $this->impliedMeasurements = array();

        // Gets the configuration either from the content element or TypoScript configuration
        if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
            $settings = $this->cObj->data['tx_rtpimgquery_breakpoints'];

        } else {
            $settings = $this->conf['breakpoints'];
        }

        // Iterates through all defined breakpoints and constructs their configuration settings
        foreach ($this->getBreakpoints() as $breakpoint) {

            if ($settings && preg_match('/' . $breakpoint . ':(\w+)/i', $settings, $width)) {
                // Matches settings like 800:500 where 500 would be the image width for the breakpoint 800
                $width = $width[1];

            } else {
                // Gets the implied image width from the breakpoint if no width was defined
                $width = $this->getModifiedWidth($breakpoint);
            }

            // Sets file.width for the current breakpoint
            $this->impliedMeasurements[$breakpoint]['file.']['width'] = $width;

            // Merges in any other configurations for the breakpoint (e.g. breakpoints.500.x)
            if (isset($this->conf['breakpoints.'][$breakpoint . '.'])) {
                $this->impliedMeasurements[$breakpoint]['file.'] = GeneralUtility::array_merge_recursive_overrule(
                    (array) $this->impliedMeasurements[$breakpoint]['file.'],
                    (array) $this->conf['breakpoints.'][$breakpoint . '.']['file.']
                );
            }

            // Processes any image height configurations
            $this->impliedMeasurements[$breakpoint]['file.']['height'] = $this->cObj->stdWrap(
                $this->impliedMeasurements[$breakpoint]['file.']['height'],
                $this->impliedMeasurements[$breakpoint]['file.']['height.']
            );

            // Gets the implied height from the width for the current breakpoint if no height was defined.
            if (!$this->impliedMeasurements[$breakpoint]['file.']['height']) {
                $this->impliedMeasurements[$breakpoint]['file.']['height'] = $this->getModifiedHeight(
                    $this->impliedMeasurements[$breakpoint]['file.']['width']
                );
            }
        }
    }

    /**
     * Gets the implied TypoScript IMAGE configuration for a given breakpoint.
     *
     * @param $breakpoint
     * @return array
     */
    private function getImpliedConfigurations($breakpoint = null)
    {
        $impliedConfigurations = $this->impliedConfigurations;

        if (!is_null($breakpoint)) {
            $impliedConfigurations = $impliedConfigurations[$breakpoint];
        }

        return $impliedConfigurations;
    }

    /**
     * Creates and returns a list (sorted in ascending order) of breakpoints and their complete
     * TypoScript IMAGE configurations. These configurations are implied by th configuration of the
     * default image, (i.e. $this->conf without any of the "breakpoint/breakpoints" settings) and their
     * implied measurements.
     *
     * @return array
     */
    private function setImpliedConfigurations()
    {
        $this->impliedConfigurations = array();

        // Creates the TypoScript configuration for each breakpoint image from the TypoScript configuration
        // of the default image and any breakpoint TypoScript configuration.
        if ($this->hasBreakpoints()) {
            foreach ($this->getBreakpoints() as $breakpoint) {

                // Copies default IMAGE TypoScript to each breakpoint.
                $this->impliedConfigurations[$breakpoint] = $this->conf;

                // Modifies image dimensions for all images (including the default)
                $this->impliedConfigurations[$breakpoint] = GeneralUtility::array_merge_recursive_overrule(
                    $this->impliedConfigurations[$breakpoint],
                    $this->getImpliedMeasurements($breakpoint)
                );

                // Unset and additional dimension settings which could disrupt the implied measurements
                unset($this->impliedConfigurations[$breakpoint]['file.']['maxW']);
                unset($this->impliedConfigurations[$breakpoint]['file.']['maxH']);
                unset($this->impliedConfigurations[$breakpoint]['file.']['minW']);
                unset($this->impliedConfigurations[$breakpoint]['file.']['minH']);
                unset($this->impliedConfigurations[$breakpoint]['file.']['width.']);
                unset($this->impliedConfigurations[$breakpoint]['file.']['height.']);

                // Unsets the "breakpoint/breakpoints" settings.
                unset($this->impliedConfigurations[$breakpoint]['file.']['breakpoint']);
                unset($this->impliedConfigurations[$breakpoint]['breakpoints']);
                unset($this->impliedConfigurations[$breakpoint]['breakpoints.']);
            }

            // Sorts list of image settings in ascending order by breakpoint
            ksort($this->impliedConfigurations);
        }
    }


    /*
     * ========================================================
     * Image dimensions
     * ========================================================
     */

    /**
     * Calculates the height for a given width based on the ratio between the default width and height
     *
     * @param string $modifiedWidth
     * @return int|string
     */
    private function getModifiedHeight($modifiedWidth)
    {
        $modifiedHeight = false;

        // The new height is the relation between the new width multiplied with the original height
        if ($modifiedWidth > 0 && $this->hasDefaultWidth() && $this->hasDefaultHeight()) {
            $modifiedHeight = $modifiedWidth / intval($this->getDefaultWidth());
            $modifiedHeight = floor($modifiedHeight * intval($this->getDefaultHeight()));
            $modifiedHeight = preg_replace('/\d+/', $modifiedHeight, $this->getDefaultHeight());
        }

        return $modifiedHeight;
    }

    /**
     * Gets the height of the default image from file.height of from the img HTML of the default image
     *
     * @return null
     */
    private function setDefaultHeight()
    {
        $this->defaultHeight = false;

        if (isset($this->conf['file.']['height'])) {
            // If set process the configuration in file.height
            $this->defaultHeight = $this->cObj->stdWrap(
                $this->conf['file.']['height'],
                $this->conf['file.']['height.']
            );

        } elseif (preg_match('/height\s*=\s*"([^"]+)"/i', $this->getDefaultImage(), $match)) {
            // Otherwise retreives the default height directly from the default image (unless the image
            // height is defined as a percentage.
            if (is_numeric($match[1])) {
                $this->defaultHeight = $match[1];
            }
        }
    }

    /**
     * @return mixed
     */
    private function getDefaultHeight()
    {
        return $this->defaultHeight;
    }

    /**
     * @return bool
     */
    private function hasDefaultHeight()
    {
        return (boolean) $this->getDefaultHeight();
    }

    /**
     * Calculates the image width for a given breakpoint based on it's ratio to the default breakpoint.
     * Takes into account special settings such as "c" and "m" (i.e. cropping and scaling parameters).
     *
     * @param $breakpoint
     * @return string
     */
    private function getModifiedWidth($breakpoint)
    {
        $modifiedWidth = false;

        // The image width for the given breakpoint is the relation of the default breakpoint to the
        // given breakpoint multiplied with the default width
        if ($this->hasDefaultWidth() && $this->hasDefaultBreakpoint()) {
            $modifiedWidth = $breakpoint / $this->getDefaultBreakpoint();
            $modifiedWidth = floor($modifiedWidth * intval($this->getDefaultWidth()));
            $modifiedWidth = preg_replace('/^\d+/', $modifiedWidth, $this->getDefaultWidth());
        }

        return $modifiedWidth;
    }

    /**
     * Gets the height of the default image from file.width of from the img HTML of the default image
     *
     * @return int|string
     */
    private function setDefaultWidth()
    {
        $this->defaultWidth = false;

        if (isset($this->conf['file.']['width'])) {
            $this->defaultWidth = $this->cObj->stdWrap($this->conf['file.']['width'], $this->conf['file.']['width.']);

        } elseif (preg_match('/width\s*=\s*"([^"]+)"/i', $this->getDefaultImage(), $match)) {
            // Avoid values which are not numeric, e.g. percentages
            if (is_numeric($match[1])) {
                $this->defaultWidth = $match[1];
            }
        }
    }

    /**
     * @return mixed
     */
    private function getDefaultWidth()
    {
        return $this->defaultWidth;
    }

    /**
     * @return bool
     */
    private function hasDefaultWidth()
    {
        return (boolean) $this->getDefaultWidth();
    }


    /*
     * ========================================================
     * Breakpoints
     * ========================================================
     */

    /**
     * Gets the default breakpoint from any of the following sources (in order of priority);
     * - As configured in the content element
     * - As configured in TypoScript
     * - The width of the default image.
     *
     * @return int
     */
    private function setDefaultBreakpoint()
    {
        $this->defaultBreakpoint = false;

        if (intval($this->cObj->data['tx_rtpimgquery_breakpoint']) > 0) {
            $this->defaultBreakpoint = intval($this->cObj->data['tx_rtpimgquery_breakpoint']);

        } elseif (intval($this->conf['breakpoint']) > 0) {
            $this->defaultBreakpoint = intval($this->conf['breakpoint']);

        } elseif ($this->hasDefaultWidth()) {
            $this->defaultBreakpoint = $this->getDefaultWidth();
        }
    }

    /**
     * Checks for a defined default breakpoint
     *
     * @return bool
     */
    private function getDefaultBreakpoint()
    {
        return $this->defaultBreakpoint;
    }

    /**
     * Checks for a defined default breakpoint
     *
     * @return bool
     */
    private function hasDefaultBreakpoint()
    {
        return (boolean) $this->getDefaultBreakpoint();
    }

    /**
     * Determines if breakpoints have been defined
     *
     * @return bool
     */
    private function hasBreakpoints()
    {
        return (boolean) $this->getBreakpoints();
    }

    /**
     * @return mixed
     */
    private function getBreakpoints()
    {
        return $this->breakpoints;
    }

    /**
     * Gets the list of defined breakpoints from the configuration sorted in descending order and
     * including the default breakpoint (i.e. the breakpoint for the default image).
     *
     *
     * @return array
     */
    private function setBreakpoints()
    {
        // The simplest case is that breakpoints are configured as "breakpoints = x, y, z" where
        // x, y & z are the breakpoints and the breakpoints correspond exactly to the image widths. e.g.
        // 400, 600, 1000 would define image widths 400, 600 & 1000 for browser widths 400, 600 & 1000
        // Alternatively the breakpoints can be configure as "breakpoints = x:a, y:b, z:c"
        // where x, y & z are the breakpoints and a, b, c are the image widths. So 400:600 would define an
        // image width of 600 at breakpoint 400.
        if (isset($this->conf['breakpoints']) || $this->cObj->data['tx_rtpimgquery_breakpoints']) {

            if ($this->cObj->data['tx_rtpimgquery_breakpoints']) {
                $this->breakpoints = str_replace(chr(10), ',', $this->cObj->data['tx_rtpimgquery_breakpoints']);

            } else {
                $this->breakpoints = $this->conf['breakpoints'];
            }

            // Create an array of breakpoints
            $this->breakpoints = GeneralUtility::trimExplode(',', $this->breakpoints, true);

            // Converts something like 610:400 to 610 (we are not interested in image widths)
            $this->breakpoints = array_filter(array_map('intval', $this->breakpoints));
        }

        // In addition to or instead of the configuration outlined above, breakpoints can be configured in more
        // detail as "breakpoints.x.file.width = n" where x is the breakpoint n is the corresponding image width.
        if (is_array($this->conf['breakpoints.'])) {

            $configuredBreakpoints = array_filter(array_map('intval', array_keys($this->conf['breakpoints.'])));

            if (is_array($configuredBreakpoints) && !empty($configuredBreakpoints)) {
                $this->breakpoints = array_merge((array) $this->breakpoints, $configuredBreakpoints);
            }
        }

        // Adds the default breakpoint to the list, but only if the list contains other breakpoints (an
        // image configuration with a single breakpoint makes no sense!)
        if (!empty($this->breakpoints)) {
            $this->breakpoints[] = $this->getDefaultBreakpoint();
        }

        // Cleans up and sorts the final list of breakpoints
        $this->breakpoints = array_map('intval', array_unique($this->breakpoints));
        sort($this->breakpoints, SORT_NUMERIC);
    }


    /*
     * =======================================================
     * Retina Images
     * =======================================================
     */

    /**
     * @return mixed
     */
    private function getPixelRatios()
    {
        return $this->pixelRatios;
    }

    /**
     * Returns an array of configured retina ratios to be used for image generation.
     *
     * @return array list of the configured retina ratios, will always include the default ratio "1"
     */
    private function setPixelRatios()
    {
        if ($this->cObj->data['tx_rtpimgquery_pixel_ratios']) {
            $this->pixelRatios = GeneralUtility::trimExplode(
                ',',
                $this->cObj->data['tx_rtpimgquery_pixel_ratios'],
                true
            );

        } else {
            $this->pixelRatios = GeneralUtility::trimExplode(
                ',',
                $this->conf['breakpoints.']['pixelRatios'],
                true
            );
        }

        // The default device resolution of 1 is always set!
        array_unshift($this->pixelRatios, 1);

        // Creates a list of unique values
        $this->pixelRatios = array_unique(array_map('floatval', $this->pixelRatios));
        sort($this->pixelRatios);
    }


    /*
     * ========================================================
     * Strategy (Template)
     * ========================================================
     */

    /**
     * Gets the initial content of the current responsive image layout
     *
     * @return string
     */
    private function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * Gets the responsive image layout
     *
     * @return array
     */
    private function setStrategy()
    {
        $layout = GeneralUtility::getFileAbsFileName(self::DEFAULT_STRATEGY);
        $this->strategy = '';

        if (isset($this->conf['breakpoints.']['layout'])) {
            $layout = GeneralUtility::getFileAbsFileName($this->conf['breakpoints.']['layout']);
        }

        if (is_readable($layout)) {
            $this->strategy = GeneralUtility::getURL($layout);
        }
    }

    private function setCacheKey()
    {
        $this->cacheKey = md5(serialize($this->getGeneratedImages()));
    }

    private function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * @return array
     */
    private function getMarkers()
    {
        return $this->markers;
    }

    /**
     * # Template Markers
     * Sets list of markers which are inserted into the responsive image layout
     */
    private function setMarkers()
    {
        $defaultImage = $this->getDefaultImage();
        if ($this->hasFluidStyle()) {
            $defaultImage = $this->insertFluidStyle($defaultImage);
        }

        $this->markers = array(
            '###DEFAULT_IMAGE###' => $defaultImage,
            '###BREAKPOINTS###' => json_encode($this->getBreakpoints()),
            '###IMAGES###' => json_encode($this->getGeneratedImages()),
            '###RATIOS###' => json_encode($this->getPixelRatios()),
            '###CACHE_KEY###' => $this->getCacheKey(),
        );
    }
}

