<?php

namespace App\Storage\StorageTypes;

use Exception;
use GuzzleHttp\Psr7\BufferStream;
use Psr\Http\Message\StreamInterface;
use Sabre\DAV\Client;

class WebDAVStorage extends AbstractStorage {
    private array $webDAVSettings;
    private Client $client;

    public function __construct(object $storage) {
        parent::__construct($storage);
        $this->baseDir = "";

        $this->webDAVSettings = array(
            'baseUri' => $storage->url,
            'userName' => $storage->username,
            'password' => $storage->password,
            'authType' => 1,
        );
        $this->client = new Client($this->webDAVSettings);
    }

    public function uploadFile(string $tempFilepath, string $targetPath, string $filename): void {
        $targetFilePath = $targetPath . $filename;

        $this->mkDirs($targetPath);
        $this->client->request('PUT', $targetFilePath, file_get_contents($tempFilepath));
        unlink($tempFilepath);
    }

    public function mkDirs(string $filepath): void {
        $pathComponents = explode("/", $filepath);

        for ($i = 0; $i < count($pathComponents); $i++) {
            $subPath = implode("/", array_slice($pathComponents, 0, $i));

            // TODO: Throws error if folder exists, ignored... should ask whether folder exists
            try {
                $this->client->request("MKCOL", $subPath);
            } catch (Exception $e) {

            }
        }
    }

    public function getFile(string $filepath): StreamInterface {
        $answer = $this->client->request('GET', $filepath)["body"];
        $s = new BufferStream();
        $s->write($answer);
        return $s;
    }

    public function moveFile(string $sourceFilePath, string $targetFilePath): void {
        $this->mkDirs($targetFilePath);

        $headers = array(
            "Destination" => $this->client->getAbsoluteUrl($targetFilePath),
            "Overwrite" => "T"
        );

        $this->client->request('MOVE', $sourceFilePath, null, $headers);
    }

    public function deleteFile(string $filepath): void {
        $this->client->request('DELETE', $filepath);
    }
}

