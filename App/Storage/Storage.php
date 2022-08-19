<?php

namespace App\Storage;

use App\Storage\StorageTypes\AbstractStorage;
use App\Storage\StorageTypes\LocalStorage;
use App\Storage\StorageTypes\WebDAVStorage;
use App\Utils\Configuration;
use LengthException;
use Psr\Log\InvalidArgumentException;

class Storage {
    public static string $defaultStorageDevice = "Default";
    public static string $temporaryStorageBasePath = "../upload_temp";

    /**
     * Get a random storage device with enough free capacity to store the given filesize
     *
     * @return AbstractStorage AbstractStorage with storage object from app-config
     */
    public static function getRandomStorageClass(int $fileSizeToStore): AbstractStorage {
        $storages = self::getStorages();

        $fittingStorages = array_values(
            array_filter($storages, function ($obj, $key) use ($fileSizeToStore) {
                return StorageSize::fitsInStorage($fileSizeToStore, $obj);
            }, ARRAY_FILTER_USE_BOTH)
        );

        $count = count($fittingStorages);
        if ($count > 0)
            return self::getStorageClass($fittingStorages[rand(0, $count - 1)]);
        else
            throw new LengthException("No storages available which can save the given file.");
    }

    /**
     * Get all storage devices from app-config
     *
     * @return array Array of storage objects
     */
    public static function getStorages(): array {
        return array_map(function ($arr) {
            return (object)$arr;
        }, Configuration::storage());
    }

    /**
     * Get the appropriate AbstractStorage implementation for the given storage object
     *
     * @param object $storage Storage to get the AbstractStorage implementation for
     * @return AbstractStorage
     */
    public static function getStorageClass(object $storage): AbstractStorage {
        return match ($storage->type) {
            "local" => new LocalStorage($storage),
            "webdav" => new WebDAVStorage($storage),
            default => throw new InvalidArgumentException("Invalid storage type given. Cannot store file"),
        };
    }

    /**
     * Get the appropriate AbstractStorage implementation for the given storage name
     *
     * @param string $storageName Storage with name to get the AbstractStorage implementation for
     * @return AbstractStorage
     */
    public static function getStorageClassByName(string $storageName): AbstractStorage {
        return self::getStorageClass(self::getStorageByName($storageName));
    }

    /**
     * Get storage device from app-config by name
     *
     * @param string $storageName Storage name
     * @return object Storage object
     */
    public static function getStorageByName(string $storageName): object {
        $arr = array_values(
            array_filter(self::getStorages(), function ($v, $k) use ($storageName) {
                return $v->name == $storageName;
            }, ARRAY_FILTER_USE_BOTH)
        );
        return $arr[0];
    }
}

