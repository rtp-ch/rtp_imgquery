<?php
namespace RTP\RtpImgquery\Configuration;

use \TYPO3\CMS\Core\Utility\GeneralUtility as GeneralUtility;

/**
 * Class Cache
 * @package RTP\RtpImgquery\Configuration
 */
class Cache
{
    /**
     * @var
     */
    private static $cache;

    /**
     * @var string
     */
    private $identity;

    /**
     * Constructor accepts a variable to generate an identifier.
     *
     * @param $identitySeed
     * @throws \BadMethodCallException
     */
    public function __construct($identitySeed)
    {
        if (empty($identitySeed)) {
            $msg = 'Missing required value to create cache identifier!';
            throw new \BadMethodCallException(1365960606, $msg);
        }

        if (is_array($identitySeed)) {
            array_multisort($identitySeed);
        }

        $this->identity = md5(serialize($identitySeed));
    }

    /**
     * Gets an item from the cache for the given key
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return self::$cache[$this->identity][$key];
    }

    /**
     * Caches a key/value pair
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        self::$cache[$this->identity][$key] = $value;
    }

    /**
     * Unsets a cached item.
     *
     * @param $key
     */
    public function uns($key)
    {
        unset(self::$cache[$this->identity][$key]);
    }

    /**
     * Checks if a cached entry exists for a given key.
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset(self::$cache[$this->identity][$key]);
    }
}

