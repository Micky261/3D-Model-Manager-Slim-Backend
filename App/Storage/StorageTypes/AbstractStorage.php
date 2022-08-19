<?php

namespace App\Storage\StorageTypes;

use Psr\Http\Message\StreamInterface;

abstract class AbstractStorage {
    public object $storage;
    public string $baseDir = "";

    public function __construct(object $storage) {
        $this->storage = $storage;
    }

    public abstract function mkDirs(string $filepath): void;

    public abstract function uploadFile(string $tempFilepath, string $targetPath, string $filename): void;

    public abstract function getFile(string $filepath): StreamInterface;

    public abstract function moveFile(string $sourceFilePath, string $targetFilePath): void;

    public abstract function deleteFile(string $filepath): void;

    public function getFilePath(int $userId, int $modelId, string $type, string $filename): string {
        return self::getFileTypePath($userId, $modelId, $type) . "$filename";
    }

    public function getFileTypePath(int $userId, int $modelId, string $type): string {
        return self::getFileBasePath($userId, $modelId) . "$type/";
    }

    public function getFileBasePath(int $userId, int $modelId): string {
        return "{$this->baseDir}$userId/$modelId/";
    }
}
