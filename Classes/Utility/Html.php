<?php
namespace RTP\RtpImgquery\Utility;

use \RTP\RtpImgquery\Service\Compatibility as Compatibility;

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
 * Class Html
 * @package RTP\RtpImgquery\Utility
 */
class Html
{
    /**
     * @var array
     */
    private static $attributes = array();

    /**
     * @param $tagName
     * @param $attributeName
     * @param $htmlSource
     * @return bool
     */
    public static function getAttributeValue($tagName, $attributeName, $htmlSource)
    {
        $htmlId = md5($htmlSource);
        $tagAttributeValue = false;

        if (!isset(self::$attributes[$htmlId])) {

            if ($tags = self::getTagsFromHtml($htmlSource)) {
                foreach ($tags[0] as $i => $tag) {
                    if ($attributes = self::getAttributesFromHtml($tag)) {
                        self::$attributes[$htmlId][][$tags[1][$i]] = array_combine($attributes[1], $attributes[2]);
                    }
                }
            }
        }

        // Gets the first matching tag/attribute from the source
        foreach (self::$attributes[$htmlId] as $propertiesOfTag) {
            if (isset($propertiesOfTag[$tagName][$attributeName])) {
                $tagAttributeValue = $propertiesOfTag[$tagName][$attributeName];
                break;
            }
        }

        return $tagAttributeValue;
    }

    /**
     * @param $htmlSource
     * @return bool
     */
    public static function getTagsFromHtml($htmlSource)
    {
        // Pattern to match opening HTML tags in the source
        $tagPattern = '%<([a-z][a-z0-9]+)[^<>]*>%i';

        // Builds a list of attributes by tag
        if (preg_match_all($tagPattern, $htmlSource, $tags)) {
            return $tags;

        }

        return false;
    }

    /**
     * @param $htmlSource
     * @return bool
     */
    public static function getAttributesFromHtml($htmlSource)
    {
        // Pattern to match attributes of a given HTML tag
        // @see http://stackoverflow.com/questions/317053/regular-expression-for-extracting-tag-attributes
        $attrPattern = '%(\S+)=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?%i';

        if (preg_match_all($attrPattern, $htmlSource, $attributes)) {
            return $attributes;
        }

        return false;
    }

}

