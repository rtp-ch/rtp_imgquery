<?php
namespace RTP\RtpImgquery\Service;

/**
 * Class Compatibility
 * @package RTP\RtpImgquery\Service
 */
class Compatibility
{
    /**
     * Abstraction method which returns System Environment Variables regardless of server OS, CGI/MODULE version etc.
     * Basically this is SERVER variables for most of them.
     * This should be used instead of getEnv() and $_SERVER/ENV_VARS to get reliable values for all situations.
     *
     * @param string $getEnvName Name of the "environment variable"/"server variable" you wish to use.
     *        Valid values are SCRIPT_NAME, SCRIPT_FILENAME, REQUEST_URI, PATH_INFO, REMOTE_ADDR, REMOTE_HOST,
     *        HTTP_REFERER, HTTP_HOST, HTTP_USER_AGENT, HTTP_ACCEPT_LANGUAGE, QUERY_STRING, TYPO3_DOCUMENT_ROOT,
     *        TYPO3_HOST_ONLY, TYPO3_HOST_ONLY, TYPO3_REQUEST_HOST, TYPO3_REQUEST_URL, TYPO3_REQUEST_SCRIPT,
     *        TYPO3_REQUEST_DIR, TYPO3_SITE_URL, _ARRAY
     * @return string Value based on the input key, independent of server/os environment.
     */
    public static function getIndpEnv($getEnvName)
    {
        if (class_exists('\TYPO3\CMS\Core\Utility\GeneralUtility')) {
            return call_user_func(array('\TYPO3\CMS\Core\Utility\GeneralUtility', 'getIndpEnv'), $getEnvName);

        } else {
            return call_user_func(array('t3lib_div', 'getIndpEnv'), $getEnvName);
        }
    }

    /**
     * Returns the absolute filename of a relative reference, resolves the "EXT:" prefix (way of referring to files
     * inside extensions) and checks that the file is inside the PATH_site of the TYPO3 installation and implies a
     * check with \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr(). Returns FALSE if checks failed.
     * Does not check if the file exists.
     *
     * @param string $filename The input filename/filepath to evaluate
     * @param boolean $onlyRelative If $onlyRelative is set (which it is by default), then only return values relative
     *        to the current PATH_site is accepted.
     * @param boolean $relToTYPO3_mainDir If $relToTYPO3_mainDir is set, then relative paths are relative to PATH_typo3
     *        constant - otherwise (default) they are relative to PATH_site
     * @return string Returns the absolute filename of $filename IF valid, otherwise blank string.
     */
    public static function getFileAbsFileName($filename, $onlyRelative = true, $relToTYPO3_mainDir = false)
    {
        if (class_exists('\TYPO3\CMS\Core\Utility\GeneralUtility')) {
            return call_user_func(
                array('\TYPO3\CMS\Core\Utility\GeneralUtility', 'getFileAbsFileName'),
                $filename,
                $onlyRelative,
                $relToTYPO3_mainDir
            );

        } else {
            return call_user_func(
                array('t3lib_div', 'getFileAbsFileName'),
                $filename,
                $onlyRelative,
                $relToTYPO3_mainDir
            );
        }
    }

    /**
     * Reads the file or url $url and returns the content
     * If you are having trouble with proxys when reading URLs you can configure your way out of that with settings
     * like $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] etc.
     *
     * @param string $url File/URL to read
     * @param integer $includeHeader Whether the HTTP header should be fetched or not. 0=disable, 1=fetch
     * header+content, 2=fetch header only
     * @param array|bool $requestHeaders HTTP headers to be used in the request
     * @param array $report Error code/message and, if $includeHeader is 1, response meta data
     * (HTTP status and content type)
     * @return mixed The content from the resource given as input. FALSE if an error has occured.
     */
    public static function getUrl($url, $includeHeader = 0, $requestHeaders = false, &$report = null)
    {
        if (class_exists('\TYPO3\CMS\Core\Utility\GeneralUtility')) {
            return \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, $includeHeader, $requestHeaders, &$report);

        } else {
            return \t3lib_div::getUrl($url, $includeHeader, $requestHeaders, &$report);
        }
    }

    /**
     * Merges two arrays recursively and "binary safe" (integer keys are
     * overridden as well), overruling similar values in the first array
     * ($arr0) with the values of the second array ($arr1)
     * In case of identical keys, ie. keeping the values of the second.
     *
     * @param array $arr0 First array
     * @param array $arr1 Second array, overruling the first array
     * @param boolean $notAddKeys If set, keys that are NOT found in $arr0 (first array) will not be set.
     * Thus only existing value can/will be overruled from second array.
     * @param boolean $includeEmptyValues If set, values from $arr1 will overrule if they are
     * empty or zero. Default: TRUE
     * @param boolean $enableUnsetFeature If set, special values "__UNSET" can be used in the second array
     * in order to unset array keys in the resulting array.
     * @return array Resulting array where $arr1 values has overruled $arr0 values
     */
    public static function arrayMergeRecursiveOverrule(
        array $arr0,
        array $arr1,
        $notAddKeys = false,
        $includeEmptyValues = true,
        $enableUnsetFeature = true
    ) {
        if (class_exists('\TYPO3\CMS\Core\Utility\GeneralUtility')) {
            return call_user_func(
                array('\TYPO3\CMS\Core\Utility\GeneralUtility', 'array_merge_recursive_overrule'),
                $arr0,
                $arr1,
                $notAddKeys,
                $includeEmptyValues,
                $enableUnsetFeature
            );

        } else {
            return call_user_func(
                array('t3lib_div', 'array_merge_recursive_overrule'),
                $arr0,
                $arr1,
                $notAddKeys,
                $includeEmptyValues,
                $enableUnsetFeature
            );
        }
    }

    /**
     * Creates an instance of a class taking into account the class-extensions
     * API of TYPO3. USE THIS method instead of the PHP "new" keyword.
     *
     * E.g. "$obj = new myclass;" should be:
     * "$obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("myclass")"
     *
     * You can also pass arguments for a constructor:
     * \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('myClass', $arg1, $arg2, ..., $argN)
     *
     * @throws \InvalidArgumentException if classname is an empty string
     * @param string $class name of the class to instantiate, must not be empty
     * @return object the created instance
     */
    public static function makeInstance($class)
    {
        $arguments = func_get_args();
        array_shift($arguments);
        array_unshift($arguments, $class);

        if (class_exists('\TYPO3\CMS\Core\Utility\GeneralUtility')) {
            return call_user_func_array(array('\TYPO3\CMS\Core\Utility\GeneralUtility', 'makeInstance'), $arguments);

        } else {
            return call_user_func_array(array('t3lib_div', 'makeInstance'), $arguments);
        }
    }

    /**
     * Explodes a string and trims all values for whitespace in the ends.
     * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
     * @see \t3lib_div::trimExplode
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode
     *
     * @param string $str The string to explode
     * @param string $delimiter Delimiter string to explode with
     * @param boolean $onlyNonEmptyValues If set (default), all empty values (='') will NOT be set in output
     * @param int $limit If positive, the result will contain a maximum of $limit elements, if negative,
     *        all components except the last -$limit are returned, if zero (default), the result is not limited at all.
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
     * Trims members of an array.
     *
     * @static
     * @param array $arr
     *
     * @return array
     */
    public static function trimMembers($arr)
    {
        return array_map(function ($item) {
            return is_string($item) ? trim($item) : $item;
        }, $arr);
    }

    /**
     * Removes empty members form an array.
     *
     * @param $arr
     * @return array
     */
    public static function stripEmpty($arr)
    {
        return array_filter($arr, function ($item) {
            if (is_string($item)) {
                return strlen($item) > 0;

            } elseif (is_null($item)) {
                return false;

            } elseif (is_array($item)) {
                return !empty($item);
            }

            // All other items (including booleans, e.g. "false") are not removed.
            return true;
        });
    }

    /**
     * Returns the absolute path to the extension with extension key $key
     * If the extension is not loaded the function will die with an error message
     * Useful for internal fileoperations
     *
     * @param $key string Extension key
     * @param $script string $script is appended to the output if set.
     * @throws \BadFunctionCallException
     * @return string
     */
    public static function extPath($key, $script = '')
    {
        if (class_exists('\TYPO3\CMS\Core\Utility\ExtensionManagementUtility')) {
            return call_user_func(
                array('\TYPO3\CMS\Core\Utility\ExtensionManagementUtility', 'extPath'),
                $key,
                $script
            );

        } else {
            return call_user_func(array('t3lib_extMgm', 'extPath'), $key, $script);
        }
    }

    /**
     * @param $content
     * @param $setup
     * @return mixed
     */
    public static function stdWrap($content, $setup)
    {
        if (class_exists('\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController')) {
            if ($GLOBALS['TSFE']->cObj instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController) {
                return $GLOBALS['TSFE']->cObj->stdWrap($content, $setup);
            }

        } else {
            if ($GLOBALS['TSFE']->cObj instanceof \tslib_fe) {
                return $GLOBALS['TSFE']->cObj->stdWrap($content, $setup);
            }
        }

        return $content;
    }
}

