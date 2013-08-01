<?php
namespace RTP\RtpImgquery\Utility;

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

            $tags = self::getTagsFromHtml($htmlSource);
            if ($tags) {

                foreach ($tags[0] as $i => $tag) {

                    $attributes = self::getAttributesFromHtml($tag);
                    if ($attributes) {
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
     * @param $tag
     * @param $attribute
     * @param $html
     * @param $value
     * @return mixed
     */
    public static function addAttributeToTag($tag, $attribute, $html, $value)
    {
        $search = '%<' . $tag . '([^>]+)/?>%im';
        $replace = '<' . $tag . '$1 ' . $attribute . '="' . $value . '">';

        return preg_replace($search, $replace, $html);
    }

    /**
     * @param $tag
     * @param $attribute
     * @param $html
     * @return mixed
     */
    public static function stripAttributeFromTag($tag, $attribute, $html)
    {
        if (preg_match('%<' . $tag . '([^>]*)' . $attribute . '\s*=\s*"[^"]+"([^>]*)/?>%im', $html)) {
            $html = preg_replace(
                '%<' . $tag . '([^>]*) ' . $attribute . '\s*=\s*"[^"]+"([^>]*)/?>%im',
                '<' . $tag . '$1$2>',
                $html
            );
        }

        return $html;
    }

    /**
     * @param $tag
     * @param $attribute
     * @param $html
     * @param $value
     * @return mixed
     */
    public static function setAttributeValue($tag, $attribute, $html, $value)
    {
        if (preg_match('%<' . $tag . '([^>]*)\s+' . $attribute . '\s*=\s*"[^"]+"([^>]*)/?>%im', $html)) {
            $html = preg_replace(
                '%<' . $tag . '([^>]*)\s+' . $attribute . '\s*=\s*"([^"]+)"([^>]*)/?>%im',
                '<' . $tag . ' $1' . $attribute . '="' . $value . '"$3>',
                $html
            );
        }

        return $html;
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
