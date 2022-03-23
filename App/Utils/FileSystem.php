<?php

namespace App\Utils;

class FileSystem {
    /**
     * https://stackoverflow.com/a/1833681
     * @param string $path Path to delete
     * @return bool Deletion successful
     */
    public static function RemoveEmptySubFolders(string $path): bool {
        $empty = true;
        foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
            $empty &= is_dir($file) && FileSystem::RemoveEmptySubFolders($file);
        }
        return $empty && rmdir($path);
    }
}
