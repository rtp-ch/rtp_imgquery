<?php

/**
 * =============================================================================
 * 
 * Modified Version of fluid:image which allows specification of breakpoints
 * 
 * =============================================================================
 * 
 * Resizes a given image (if required) and renders the respective img tag
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:image src="EXT:myext/Resources/Public/typo3_logo.png" alt="alt text" />
 * </code>
 * <output>
 * <img alt="alt text" src="typo3conf/ext/myext/Resources/Public/typo3_logo.png" width="396" height="375" breakpoint="900" breakpoints="600:300, 400"/>
 * or (in BE mode):
 * <img alt="alt text" src="../typo3conf/ext/viewhelpertest/Resources/Public/typo3_logo.png"  breakpoint="900" breakpoints="600:300, 400" />
 * </output>
 *
 * <code title="Inline notation">
 * {f:image(src: 'EXT:viewhelpertest/Resources/Public/typo3_logo.png', alt: 'alt text', minWidth: 30, maxWidth: 40)}
 * </code>
 * <output>
 * <img alt="alt text" src="../typo3temp/pics/f13d79a526.png" width="40" height="38" />
 * (depending on your TYPO3s encryption key)
 * </output>
 *
 * <code title="non existing image">
 * <f:image src="NonExistingImage.png" alt="foo" />
 * </code>
 * <output>
 * Could not get image resource for "NonExistingImage.png".
 * </output>
 *
* TODO: Refactor & merge functionality of IMAGE xclass
 */
class Tx_RtpImgquery_ViewHelpers_ImageViewHelper extends Tx_Fluid_ViewHelpers_ImageViewHelper
{
    /**
     * @var string
     */
    const IMAGE_STYLE           = 'width: 100%; height: auto;';

    /**
     * Default layout
     *
     * @var string
     */
    const DEFAULT_LAYOUT      = 'EXT:rtp_imgquery/Resources/Private/Templates/rtp_imgquery.min.html';

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
     * @param null $layout
     * @return string
     */
    public function render($src, $width = null, $height = null, $minWidth = null, $minHeight = null, $maxWidth = null,
                           $maxHeight = null, $breakpoints = null, $breakpoint = null, $layout = null)
    {
        $this->setConf($src, $width, $height, $minWidth, $minHeight, $maxWidth,
                       $maxHeight, $breakpoints, $breakpoint, $layout);

if( 1 === 0) {
    t3lib_utility_Debug::debugInPopUpWindow(array(
        '****************'  => '****************',
        'conf'              => $this->conf,
        'hasBreakpoints'    => $this->hasBreakpoints()
    ));
}

        if( $this->hasBreakpoints() ) {
            $this->tag->addAttribute('style', self::IMAGE_STYLE);
            $imageHtml = $this->responsiveImage();

if( 1 === 0) {
    t3lib_utility_Debug::debugInPopUpWindow(array(
        'id'                => $this->id(),
        'defaultImage'      => $this->defaultImage(),
        'defaultWidth'      => $this->defaultWidth(),
        'defaultBreakpoint' => $this->defaultBreakpoint(),
        'breakpoints'       => $this->breakpoints(),
        'images'            => $this->images(),
        'attributes'        => $this->attributes(),
        'markers'           => $this->getMarkers()
    ));
}

        // Otherwise create the default image
        } else {
            $imageHtml = $this->defaultImage();
        }

if( 1 === 0) {
    t3lib_utility_Debug::debugInPopUpWindow(array(
        'imageHtml'         => $imageHtml,
        '****************'  => '****************',
    ));
}
            return $imageHtml;
    }

    /*
     * ========================================================
     * Main
     * ========================================================
     */

    private function responsiveImage()
    {
        $this->setMarkers();
        $this->cleanMarkers();

        if(count($this->breakpoints()) > 1) {
            $search     = array_keys($this->getMarkers());
            $replace    = $this->getMarkers();
            $content    = $this->layoutContent();
            return html_entity_decode(str_ireplace($search, $replace, $content));
        } else {
            return $this->defaultImage();
        }
    }

    /*
     * ========================================================
     * Breakpoints
     * ========================================================
     */

    private function hasDefaultBreakpoint()
    {
        return (boolean) $this->conf['breakpoint'];
    }

    private function defaultBreakpoint()
    {
        if( is_null($this->defaultBreakpoint) ) {
            if( $this->conf['breakpoint'] ) {
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
        if( is_null($this->breakpoints) ) {

            $breakpoints = t3lib_div::trimExplode(',', $this->conf['breakpoints'], true);

            // Adds the default breakpoint to the list if a breakpoint configuration exists or
            // breakpoints have been defined.
            if(!empty($breakpoints) || $this->hasDefaultBreakpoint()) {
                $breakpoints[] = $this->defaultBreakpoint();
                // Ensures the list is unique and converts values like 610:400 to 610
                $breakpoints = array_map('intval', array_unique($breakpoints));
                // Sorts the list numerically in reverse order (highest first(
                rsort($breakpoints, SORT_NUMERIC);
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
        if( is_null($this->breakpointConfigurations) ) {

            $this->breakpointConfigurations = array();

            if($this->hasBreakpoints()) {

                // Breakpoints configuration
                if( isset($this->conf['breakpoints']) ) {

                    // Gets the list of breakpoints and their respective widths
                    $breakpoints = t3lib_div::trimExplode(',', $this->conf['breakpoints'], true);

                    // Validation and width configuration for each breakpoint
                    while($breakpoint = array_shift($breakpoints)) {

                        $breakpointSettings = t3lib_div::trimExplode(':', $breakpoint, true, 2);
                        $breakpointValue = intval($breakpointSettings[0]);

                        if($breakpointValue > 0) {

                            // The image width for the breakpoint has either been defined or is derived
                            // from the default width and default breakpoint.
                            if( intval($breakpointSettings[1]) > 0 ) {
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

    /**
     * Removes a breakpoint from the responsive image
     *
     * @param $breakpoint
     */
    private function removeBreakpoint($breakpoint)
    {
        $index = array_search($breakpoint, $this->breakpoints);
        if($index >= 0) {
            unset($this->breakpoints[$index]);
            if($this->images[$breakpoint]) {
                unset($this->images[$breakpoint]);
            }
            if(isset($this->attributes[$breakpoint])) {
                unset($this->attributes[$breakpoint]);
            }
        }
    }

    /*
     * ========================================================
     * Image dimensions
     * ========================================================
     */

    private function hasDefaultWidth()
    {
        return (boolean) $this->defaultWidth();
    }

    private function defaultWidth()
    {
        if( is_null($this->defaultWidth) ) {
            $this->defaultWidth = false;
            if(preg_match('/width\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if( is_numeric($match[1]) ) {
                    $this->defaultWidth = $match[1];
                } elseif($this->defaultSource()) {
                    // TODO: Get image dimensions from image source
                }
            }
        }
        return $this->defaultWidth;
    }

    private function hasDefaultHeight()
    {
        return (boolean) $this->defaultHeight();
    }

    private function defaultHeight()
    {
        if( is_null($this->defaultHeight) ) {
            $this->defaultHeight = false;
            if(preg_match('/height\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                // Avoid values which are not numeric, e.g. percentages
                if( is_numeric($match[1]) ) {
                    $this->defaultHeight = $match[1];
                } elseif($this->defaultSource()) {
                    // TODO: Get image dimensions from image source
                }
            }
        }
        return $this->defaultHeight;
    }

    private function getBreakpointWidth($breakpoint)
    {
        if($this->hasDefaultWidth()) {
            return floor(($breakpoint / $this->defaultBreakpoint()) * $this->defaultWidth());
        }
    }

    private function getHeightForWidth($width)
    {
        if($this->hasDefaultWidth() && $this->hasDefaultHeight()) {
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
        if( is_null($this->id) ) {
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
    private function setConf($src, $width, $height, $minWidth, $minHeight,
                             $maxWidth, $maxHeight, $breakpoints, $breakpoint, $layout)
    {
        $this->conf['src']          = $src;
        $this->conf['width']        = $width;
        $this->conf['height']       = $height;
        $this->conf['minWidth']     = $minWidth;
        $this->conf['minHeight']    = $minHeight;
        $this->conf['maxWidth']     = $maxWidth;
        $this->conf['maxHeight']    = $maxHeight;
        $this->conf['breakpoints']  = $breakpoints;
        $this->conf['breakpoint']   = intval($breakpoint) > 0 ? intval($breakpoint) : false;
        $this->conf['layout']       = $layout;
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
        if( is_null($this->defaultImage) ) {
            $this->defaultImage = parent::render(
                $this->conf['src'],
                $this->conf['width'],
                $this->conf['height'],
                $this->conf['minWidth'],
                $this->conf['minHeight'],
                $this->conf['maxWidth'],
                $this->conf['maxHeight'],
                $this->conf['breakpoints'],
                $this->conf['breakpoint']
            );
        }
        return $this->defaultImage;
    }

    public function defaultSource()
    {
        if( is_null($this->defaultSource) ) {
            $this->defaultSource = false;
            if(preg_match('/src\s*=\s*"([^"]+)"/i', $this->defaultImage(), $match)) {
                $defaultSource = t3lib_div::getFileAbsFileName($match[1]);
                if(is_readable($defaultSource)) {
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
        if( is_null($this->images) ) {

            $this->images = array();

            if($this->hasBreakpoints()) {

                // Renders an image for each breakpoint/width combination
                foreach($this->breakpoints() as $breakpoint) {
                    $width = $this->breakpointConfiguration($breakpoint);
                    $this->images[$breakpoint] = parent::render(
                        $this->conf['src'],
                        $width,
                        $this->getHeightForWidth($width)
                    );
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
        if( is_null($this->attributes) ) {
            $this->attributes = array();
            foreach ($this->images() as $breakpoint => $image) {
                // http://stackoverflow.com/questions/317053/regular-expression-for-extracting-tag-attributes
                if(preg_match_all('/(\S+)=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/s', $image, $attributes)) {
                    $this->attributes[$breakpoint] =  array_combine($attributes[1], $attributes[2]);
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
        if( is_null(self::$layoutContent[$this->layout()]) ) {
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
        if( is_null($this->layout) ) {
            $this->layout = t3lib_div::getFileAbsFileName(self::DEFAULT_LAYOUT);
            if( isset($this->conf['layout']) ) {
                $layout = $this->conf['layout'];
                if( is_readable($layout) ) {
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
    private function setMarkers()
    {
        $this->markers = array(
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
     * Gets the marker array which is inserted into the responsive image layout
     *
     * @return array
     */
    private function getMarkers()
    {
        return $this->markers;
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
}