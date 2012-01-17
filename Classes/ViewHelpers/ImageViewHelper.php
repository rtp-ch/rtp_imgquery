<?php

/**
 * =============================================================================
 * 
 * Modified Version of fluid:image which allows to specify height and with of the image
 * tag separate
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
 * <img alt="alt text" src="typo3conf/ext/myext/Resources/Public/typo3_logo.png" width="396" height="375" tagWidth="100%" tagSize="1"/>
 * or (in BE mode):
 * <img alt="alt text" src="../typo3conf/ext/viewhelpertest/Resources/Public/typo3_logo.png" width="100%" />
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
 */
class Tx_Rtp_ImgQuery_ViewHelpers_ImageViewHelper extends Tx_Fluid_ViewHelpers_ImageViewHelper 
{
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
    
    private $images;

    private $breakpoints;

    private $defaultWidth;

    private $defaultBreakpoint;

    private $attributes;

    /**
     * Resizes a given image (if required) and renders the respective img tag
     * @see http://typo3.org/documentation/document-library/references/doc_core_tsref/4.2.0/view/1/5/#id4164427
     *
     * @param string $src
     * @param string $width width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
     * @param string $height height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
     * @param integer $minWidth minimum width of the image
     * @param integer $minHeight minimum height of the image
     * @param integer $maxWidth maximum width of the image
     * @param integer $maxHeight maximum height of the image
     * @param string $tagWidth width of the image tag
     * @param string $tagHeight height of the image tag
     * @param string $tagSize use separate configuration of width/height of the image tag ($tagWidth/$tagHeight)
     *
     * @return string rendered tag.
     * @author Sebastian Böttger <sboettger@cross-content.com>
     * @author Bastian Waidelich <bastian@typo3.org>
     */

    /**
     * @param $src
     * @param null $width
     * @param null $height
     * @param null $minWidth
     * @param null $minHeight
     * @param null $maxWidth
     * @param null $maxHeight
     * @param null $tagWidth
     * @param null $tagHeight
     * @param null $breakpoints
     * @throws Tx_Fluid_Core_ViewHelper_Exception
     */
    public function render($src, $width = null, $height = null, $minWidth = null, $minHeight = null,
                           $maxWidth = null, $maxHeight = null, $breakpoints = null, $breakpoint = null)
    {

        $defaultImage = parent::render($src, $width, $height, $minWidth, $minHeight, $maxWidth, $maxHeight);

        $this->conf['breakpoints'] = $breakpoints;
        $this->conf['breakpoint']  = $breakpoint;


        if( $this->hasBreakpoints() ) {

            $this->id = $this->id();

            if($defaultWidth = $this->getDefaultWidth($defaultImage)) {
                $breakpoints = $this->getBreakpoints($breakpoints);
                $defaultBreakpoint = (intval($breakpoint) > 0) ? intval($breakpoint) : $defaultWidth;
                $breakpoints[$defaultBreakpoint] = $defaultWidth;
                rsort($breakpoints, SORT_NUMERIC);

                foreach($breakpoints as $breakpoint => $width) {

                    //$images[]

                }
            }
        }
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

            // Breakpoint configuration as "breakpoints = x:a, y:b, z:c" where x, y & z are the breakpoints and
            // a, b, c are the image widths.
            if( isset($this->conf['breakpoints']) ) {
                $breakpoints = t3lib_div::trimExplode(',', $this->conf['breakpoints'], true);
                while($breakpoint = array_shift($breakpoints)) {
                    $breakpointSetting = t3lib_div::trimExplode(',', $breakpoint, true, 2);
                    if(isset($breakpointSetting[1])) {
                        $breakpointConfigurations[$breakpoint]['file.']['width'] = $breakpointSetting[1];
                    }
                }
            }

            // Configuration "breakpoints.x.file.width = n" where x is the breakpoint and n is the
            // corresponding image width.
            if(is_array($this->conf['breakpoints.']) && !empty($this->conf['breakpoints.'])) {
                foreach($this->conf['breakpoints.'] as $breakpoint => $breakpointConfiguration) {
                    if(is_numeric(substr($breakpoint, 0, -1))) {
                        $breakpointConfigurations[$breakpoint] =
                            array_merge((array) $breakpointConfigurations[$breakpoint], $breakpointConfiguration);
                    }
                }
            }

            $this->registry[$this->id]['breakpointConfigurations'] = $breakpointConfigurations;
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
        if( !isset($this->breakpoints) ) {

            $breakpoints = array();

            // The simplest case is that breakpoints are configured as "breakpoints = x, y, z" where
            // x, y & z are the breakpoints and the corresponding image widths. Alternatively the breakpoints can
            // be configure as "breakpoints = x:a, y:b, z:c" where x, y & z are the breakpoints and a, b, c
            // are the image widths.
            if( isset($this->conf['breakpoints']) ) {
                $breakpoints = t3lib_div::trimExplode(',', $this->conf['breakpoints'], true);
                // Converts something like 610:400 to 610
                $breakpoints = array_map('intval', $breakpoints);
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
     * Instance id: a unique Id for each IMAGE object.
     *
     * @return string
     */
    private function id()
    {
        return md5(uniqid(mt_rand(), true));
    }









    private function getBreakpoints($settings)
    {
        $settings = t3lib_div::trimExplode(',', $settings, true);
        if(is_array($settings) && !empty($settings)) {
            while($setting = array_shift($settings)) {
                $setting = t3lib_div::trimExplode(':', $settings, true);
                $breakpoint = intval($setting[0]);
                if($breakpoint > 0)
                $breakpoints[$breakpoint] = $setting[1] ? $setting[1] : $breakpoint;
            }
        }
    }

    private function getDefaultWidth($imgTag)
    {
        $defaultWidth = fasle;
        if(preg_match('/width=\"([^\"]+)\"/ui', $imgTag, $match)) {
            if( is_int($match[1]) ) {
                $defaultWidth = $match[1];
            }
        }
        return $defaultWidth;
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
}