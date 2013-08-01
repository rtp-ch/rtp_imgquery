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
 * Class TypoScript
 * @package RTP\RtpImgquery\Utility
 */
class TypoScript
{
    /**
     *
     *
     * @param $newDim
     * @param $oldDim
     * @return mixed
     */
    public static function getNewDimension($newDim, $oldDim)
    {
        $newDim = preg_replace('/^\d+/', $newDim, $oldDim);

        if (preg_match('/\d+[A-Za-z]{0,1}/', $newDim, $match)) {
            $newDim = $match[0];
        }

        return $newDim;
    }

    /**
     * Removes superfluous typoscript configurations
     *
     * @param $configuration
     * @return mixed
     */
    public static function strip($configuration)
    {
        // Unsets "breakpoint/breakpoints" definitions which are not valid for image objects
        unset($configuration['breakpoint']);
        unset($configuration['breakpoints']);
        unset($configuration['breakpoints.']);

        // Unsets additional image dimension settings which would disrupt the implied measurements
        // for the current breakpoint.
        unset($configuration['file.']['maxW']);
        unset($configuration['file.']['maxH']);
        unset($configuration['file.']['minW']);
        unset($configuration['file.']['minH']);

        return $configuration;
    }
}
