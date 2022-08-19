<?php

namespace App\Storage;

class FileSystem {
    /**
     * Removes all empty temporary folders
     *
     * @return bool Deletion successful
     */
    public static function removeTempFolders(): bool {
        $empty = true;
        foreach (glob(Storage::$temporaryStorageBasePath . DIRECTORY_SEPARATOR . "*") as $file) {
            $empty &= is_dir($file) && self::removeEmptyFolders($file);
        }
        return $empty;
    }

    /**
     * Removes all subfolders in the given path recursively if they are empty and then tries to delete the given folder
     * https://stackoverflow.com/a/1833681
     *
     * @param string $path Path to delete
     * @return bool Deletion successful
     */
    public static function removeEmptyFolders(string $path): bool {
        $empty = true;
        foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
            $empty &= is_dir($file) && self::removeEmptyFolders($file);
        }
        return $empty && rmdir($path);
    }
}
