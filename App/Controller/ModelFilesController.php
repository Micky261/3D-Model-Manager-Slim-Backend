<?php

namespace App\Controller;

use App\Models\FileType;
use App\Models\Model;
use App\Models\ModelFile;
use App\Models\ServerMessage;
use App\Storage\FileSystem;
use App\Storage\Storage;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use FaaPz\PDO\Clause\Limit;
use GuzzleHttp\Psr7\LazyOpenStream;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelFilesController {
    public function getFilesWithType(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $type = $args["type"];

        $files = DB::connection()->select()
            ->from("model_files")
            ->where(new Grouping(
                "AND",
                new Conditional("model_id", "=", $modelId),
                new Conditional("user_id", "=", $userId),
                new Conditional("type", "=", $type)
            ))
            ->execute()
            ->fetchAll();

        $response->getBody()->write(json_encode($files));
        return $response;
    }

    public function getMainImage(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $file = DB::connection()
            ->select()
            ->from("model_files")
            ->where(new Grouping(
                "AND",
                new Conditional("model_id", "=", $modelId),
                new Conditional("user_id", "=", $userId),
                new Conditional("type", "=", "image")
            ))
            ->orderBy("position", "ASC")
            ->limit(new Limit(1))
            ->execute()->fetch();

        if ($file !== false) {
            $storage = Storage::getStorageClassByName($file["storage"]);
            $filepath = $storage->getFilePath($userId, $modelId, $file["type"], $file["filename"]);

            $fileStream = $storage->getFile($filepath);
            return $response->withBody($fileStream)->withHeader("Content-Type", FileType::getMimeTypeFromFilename($file["filename"]));
        }

        $fileStream = new LazyOpenStream("./assets/no_image.png", "r");
        return $response->withBody($fileStream)->withHeader("Content-Type", FileType::getMimeType("png"));
    }

    public function getFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $fileId = $args["fileId"];

        $file = ModelFile::getFile($userId, $fileId);
        $storage = Storage::getStorageClassByName($file["storage"]);
        $filepath = $storage->getFilePath($userId, $file["model_id"], $file["type"], $file["filename"]);

        $fileStream = $storage->getFile($filepath);
        return $response->withBody($fileStream)->withHeader("Content-Type", FileType::getMimeTypeFromFilename($file["filename"]));

//        $response->getBody()->write((new ServerMessage(
//            "The given file could not be found in the storage.",
//            "FileNotFoundOnStorage",
//            additional_information: $fileId
//        ))->toJson());
//        return $response->withStatus(409);
    }

    public function deleteFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $fileId = $args["fileId"];

        $file = ModelFile::getFile($userId, $fileId);
        $storage = Storage::getStorageClassByName($file["storage"]);
        $filepath = $storage->getFilePath($userId, $file["model_id"], $file["type"], $file["filename"]);

        $storage->deleteFile($filepath);
        ModelFile::deleteFile($userId, $fileId);

//                $response->getBody()->write((new ServerMessage(
//                    "The file could not be deleted.",
//                    "FileCouldNotBeDeleted",
//                    additional_information: $fileId
//                ))->toJson());
//                return $response->withStatus(409);

        return $response;
    }

    public function updateFiles(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $body = $request->getParsedBody();

        foreach ($body as $newFileData) {
            $fileId = $newFileData["id"];
            $newPosition = $newFileData["position"];

            $oldFileData = ModelFile::getFile($userId, $fileId);
            if (!is_null($newFileData["type"]) && $oldFileData != false) {
                $storage = Storage::getStorageClassByName($oldFileData["storage"]);

                $oldPosition = $oldFileData["position"];
                $oldFileType = $oldFileData["type"];
                $oldFilename = $oldFileData["filename"];

                $newFileType = $newFileData["type"];
                $newFilename = $newFileData["filename"];

                $updated = false;

                if ($oldFileType != $newFileType || $oldFilename != $newFilename) {
                    if (ModelFile::fileExists($userId, $modelId, $newFileType, $newFilename)) {
                        $response->getBody()->write((new ServerMessage(
                            "Target file already exists.",
                            "TargetAlreadyExists",
                            additional_information: [$oldFileType, $newFileType, $oldFilename, $newFilename]
                        ))->toJson());
                        return $response->withStatus(409);
                    } else {
                        $sourceFilePath = $storage->getFilePath($userId, $modelId, $oldFileType, $oldFilename);
                        $targetFilePath = $storage->getFilePath($userId, $modelId, $newFileType, $newFilename);
                        $storage->moveFile($sourceFilePath, $targetFilePath);
                    }

                    $updated = true;
                }

                $updated = $updated || ($oldPosition != $newPosition);

                if ($updated) {
                    DB::connection()
                        ->update([
                            "type" => $newFileType,
                            "filename" => $newFilename,
                            "position" => $newPosition
                        ])
                        ->table("model_files")
                        ->where(new Grouping(
                            "AND",
                            new Conditional("id", "=", $fileId),
                            new Conditional("user_id", "=", $userId)
                        ))->execute();
                }
            }
        }

        $response->getBody()->write(json_encode(ModelFile::getFiles($userId, $modelId)));
        return $response;
    }

    public function getFiles(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $response->getBody()->write(json_encode(ModelFile::getFiles($userId, $modelId)));
        return $response;
    }

    /**
     * @throws ZipException
     */
    public function downloadZipFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $type = $args["type"];

        $files = $type == "all" ? ModelFile::getFiles($userId, $modelId) : ModelFile::getFilesByType($userId, $modelId, $type);

        $zip = new ZipFile();
        foreach ($files as $file) {
            $fileType = $file["type"];
            $filename = $file["filename"];
            $storage = Storage::getStorageClassByName($file["storage"]);

            $filepath = $storage->getFilePath($userId, $modelId, $fileType, $filename);

            $stream = $storage->getFile($filepath);
            $zip->addFromString("{$fileType}/{$filename}", $stream->getContents());
        }
        try {
            return $zip->outputAsPsr7Response($response, "zip.zip", "application/zip");
        } catch (ZipException) {
            return $response->withStatus(500);
        }

//        $basePath = ModelFile::getFileBasePath($userId, $modelId) . (($type == "all") ? "" : "$type/");
//
//        if (is_dir($basePath)) {
////            $zipPath = "../upload_temp/zips/";
////            if (!is_dir($zipPath)) mkdir($zipPath, 0755, true);
////            $zipFilePath = "$zipPath{$userId}_$modelId.zip";
////            if (file_exists($zipFilePath)) unlink($zipFilePath);
//
//            $zip = new ZipFile();
//            try {
//                return $zip->addDirRecursive($basePath)
//                    ->outputAsPsr7Response($response, "zip.zip", "application/zip");
//            } catch (ZipException) {
//                return $response->withStatus(500);
//            }
//
////            $zip->saveAsFile($zipFilePath);
////            $zip->close();
////            $fileStream = new LazyOpenStream($zipFilePath, "r");
////            return $response->withBody($fileStream)->withHeader("Content-Type", "application/zip");
//        }
//
//        return $response->withStatus(404);
    }

    public function saveFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $body = $request->getParsedBody();
        $filename = $body["filename"];
        $type = $body["type"];

        if (ModelFile::fileExists($userId, $modelId, $type, $filename)) {
            $response->getBody()->write((new ServerMessage(
                "File already exists.",
                "TargetAlreadyExists",
                $modelId
            ))->toJson());
            return $response->withStatus(409);
        } else { // File doesn't exist
            $time = $body["timestamp"];
            $chunkPath = Storage::$temporaryStorageBasePath . "/chunked/$userId/$time/";
            if (!is_dir($chunkPath)) mkdir($chunkPath, 0755, true);

            $chunk = $body["chunk"];
            $total = $body["totalChunks"];

            $file = $request->getUploadedFiles()["file"];
            $chunkName = "{$chunk}__$filename";
            $file->moveTo($chunkPath . $chunkName);

            if (($chunk + 1) == $total) {
                $tempFilePath = $chunkPath . $filename;
                $tempFile = fopen($tempFilePath, "x");

                for ($i = 0; $i < $total; $i++) {
                    $chunkFilePath = "$chunkPath/{$i}__$filename";
                    $chunkFile = fopen($chunkFilePath, "r");
                    fwrite($tempFile, fread($chunkFile, filesize($chunkFilePath)));
                    fclose($chunkFile);
                    unlink($chunkFilePath);
                }

                fclose($tempFile);
                $filesize = filesize($tempFilePath);

                $storage = Storage::getRandomStorageClass($filesize);
                $targetFilePath = $storage->getFileTypePath($userId, $modelId, $type);

                $storage->uploadFile($tempFilePath, $targetFilePath, $filename);
                ModelFile::createFileDBEntry($userId, $modelId, $storage->storage->name, $type, $filename, $filesize);

                FileSystem::removeTempFolders();
            }
        }

        return $response;
    }
}
