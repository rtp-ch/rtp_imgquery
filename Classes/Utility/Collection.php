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
class Collection
{

    /**
     * Explodes a string and trims all values for whitespace in the ends.
     * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
     * @see \t3lib_div::trimExplode
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode
     *
     * @param string  $str                The string to explode
     * @param string  $delimiter          Delimiter string to explode with
     * @param boolean $onlyNonEmptyValues If set (default), all empty values (='') will NOT be set in output
     * @param int     $limit              If positive, the result will contain a maximum of $limit elements,
     *        if negative, all components except the last -$limit are returned, if zero (default), the result is
     *        not limited at all.
     *
     * @return array
     */
    public static function trimExplode($str, $delimiter = ',', $onlyNonEmptyValues = true, $limit = 0)
    {
        $arr = array();

        if (is_string($str)) {

            // Explodes the string into an array
            $arr = explode($delimiter, $str);

            // Trims the array members
            $arr = (array) self::trimMembers($arr);

            // Strips empty members form the array
            if ($onlyNonEmptyValues) {
                $arr = (array) self::stripEmpty($arr);
            }

            // $limit cannot be larger than the number of array members
            $limit = (is_int($limit) && abs($limit) < count($arr)) ? $limit : 0;

            // Apply $limit to the array
            if ($limit > 0) {
                $arr =  array_slice($arr, 0, $limit);

            } elseif ($limit < 0) {
                $arr = array_slice($arr, $limit);
            }
        }

        return $arr;
    }

    /**
     * Trims members of an array which are strings
     *
     * @param  array $arr
     * @return array
     */
    public static function trimMembers($arr)
    {
        return array_map(
            function ($item) {
                return is_string($item) ? trim($item) : $item;
            },
            $arr
        );
    }

    /**
     * Removes empty members form an array.
     *
     * @param $arr
     * @return array
     */
    public static function stripEmpty($arr)
    {
        return array_filter(
            $arr,
            function ($item) {
                if (is_string($item)) {
                    return strlen($item) > 0;

                } elseif (is_null($item)) {
                    return false;

                } elseif (is_array($item)) {
                    return !empty($item);
                }

                // All other items (including booleans, e.g. "false") are not removed.
                return true;
            }
        );
    }
}
