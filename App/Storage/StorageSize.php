<?php

namespace App\Storage;

use App\Models\ModelFile;

class StorageSize {
    static string $capacityRegex = "/(\d+)([BKMGT])?/i";

    public static function fitsInStorage(int $filesize, object $storage): bool {
        return self::getFreeSizeOfStorageInByte($storage) >= $filesize;
    }

    public static function getFreeSizeOfStorageInByte(object $storage): int {
        return self::getSizeOfStorageInByte($storage) - self::getOccupiedSizeOfStorageInByte($storage);
    }

    public static function getSizeOfStorageInByte(object $storage): int {
        $capacity = $storage->capacity;
        $sizeArr = preg_split(self::$capacityRegex, $capacity, flags: PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (count($sizeArr) == 1)
            return intval($sizeArr[0]);
        else if (count($sizeArr) == 2)
            return intval($sizeArr[0]) * self::unitToMagnitude($sizeArr[1]);
        else
            return 0;
    }

    private static function unitToMagnitude(string $unit): int {
        return match ($unit) {
            "K" => 1000,
            "M" => 1000000,
            "G" => 1000000000,
            "T" => 1000000000000,
            default => 1,
        };
    }

    public static function getOccupiedSizeOfStorageInByte(object $storage): int {
        return ModelFile::getStorageSize($storage->name);
    }
}
