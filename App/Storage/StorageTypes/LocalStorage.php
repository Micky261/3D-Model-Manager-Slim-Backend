<?php

namespace App\Storage\StorageTypes;

use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\StreamInterface;

class LocalStorage extends AbstractStorage {
    public function __construct(object $storage) {
        parent::__construct($storage);
        $this->baseDir = $storage->url;
    }

    public function uploadFile(string $tempFilepath, string $targetPath, string $filename): void {
        $this->mkDirs($targetPath);
        rename($tempFilepath, $targetPath . $filename);
    }

    public function mkDirs(string $filepath): void {
        if (!is_dir($filepath)) mkdir($filepath, 0755, true);
    }

    public function moveFile(string $sourceFilePath, string $targetFilePath): void {
        $this->mkDirs($targetFilePath);
        rename($sourceFilePath, $targetFilePath);
    }

    public function getFile(string $filepath): StreamInterface {
        return new LazyOpenStream($filepath, "r");
    }

    public function deleteFile(string $filepath): void {
        unlink($filepath);
    }
}
