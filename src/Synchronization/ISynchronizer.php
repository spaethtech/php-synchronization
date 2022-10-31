<?php
declare(strict_types=1);

namespace SpaethTech\Synchronization;

/**
 * Interface ISynchronizer
 *
 * @package SpaethTech\Synchronization
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 */
interface ISynchronizer
{
    /**
     * @return string Returns a file path for this Synchronizer's cache file.
     */
    function getMapFile(): string;

    /**
     * @param mixed $object The source object for which to determine the appropriate lookup name.
     * @return string Returns a unique name for which to reference all lookups stored in the SyncMap.
     */
    function getSourceName($object): string;

    /**
     * @param mixed $object The source object for which to determine the appropriate "mapped" key.
     * @return string Returns a unique key for which to reference all comparisons stored in the SyncMap.
     */
    function getSourceKey($object): string;

    /**
     * @param mixed $object The source object for which to determine the appropriate "mapped" value.
     * @return mixed Returns any possible scalar, array or object value to be stored in the SyncMap.
     */
    function getSourceValue($object);

    /**
     * @param mixed $object The destination object for which to determine the appropriate lookup name.
     * @return string Returns a unique name for which to reference all lookups stored in the SyncMap.
     */
    function getDestinationName($object): string;

    /**
     * @param mixed $object The destination object for which to determine the appropriate "mapped" key.
     * @return string Returns a unique key for which to reference all comparisons stored in the SyncMap.
     */
    function getDestinationKey($object): string;

    /**
     * @param mixed $object The destination object for which to determine the appropriate "mapped" value.
     * @return mixed Returns any possible scalar, array or object value to be stored in the SyncMap.
     */
    function getDestinationValue($object);

}
