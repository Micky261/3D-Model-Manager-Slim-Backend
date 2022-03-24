<?php

namespace App\Controller;

use App\Models\FileType;
use App\Models\ModelFile;
use App\Models\ServerMessage;
use App\Utils\DB;
use App\Utils\FileSystem;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use FaaPz\PDO\Clause\Limit;
use GuzzleHttp\Psr7\LazyOpenStream;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelFilesController {
    public function getFiles(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $response->getBody()->write(json_encode(ModelFile::getFiles($userId, $modelId)));
        return $response;
    }

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

    public function getFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $fileId = $args["fileId"];

        $file = ModelFile::getFile($userId, $fileId);
        $filepath = ModelFile::getFilePath($userId, $file["model_id"], $file["type"], $file["filename"]);

        if (file_exists($filepath)) {
            $fileStream = new LazyOpenStream($filepath, "r");
            return $response->withBody($fileStream)->withHeader("Content-Type", FileType::getMimeTypeFromFilename($file["filename"]));
        }

        $response->getBody()->write((new ServerMessage(
            "The given file could not be found in the storage.",
            "FileNotFoundOnStorage",
            additional_information: $fileId
        ))->toJson());
        return $response->withStatus(409);
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
            $filepath = ModelFile::getFilePath($userId, $modelId, $file["type"], $file["filename"]);

            if (file_exists($filepath)) {
                $fileStream = new LazyOpenStream($filepath, "r");
                return $response->withBody($fileStream)->withHeader("Content-Type", FileType::getMimeTypeFromFilename($file["filename"]));
            }
        }

        $fileStream = new LazyOpenStream("./assets/no_image.png", "r");
        return $response->withBody($fileStream)->withHeader("Content-Type", FileType::getMimeType("png"));
    }


    public function deleteFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $fileId = $args["fileId"];

        $file = ModelFile::getFile($userId, $fileId);
        $filepath = ModelFile::getFilePath($userId, $file["model_id"], $file["type"], $file["filename"]);

        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                DB::connection()->delete()
                    ->from("model_files")
                    ->where(new Grouping(
                        "AND",
                        new Conditional("id", "=", $fileId),
                        new Conditional("user_id", "=", $userId)
                    ))
                    ->execute();
            } else {
                $response->getBody()->write((new ServerMessage(
                    "The file could not be deleted.",
                    "FileCouldNotBeDeleted",
                    additional_information: $fileId
                ))->toJson());
                return $response->withStatus(409);
            }
        }

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
                $oldPosition = $oldFileData["position"];
                $oldFileType = $oldFileData["type"];
                $oldFileName = $oldFileData["filename"];
                $newFileType = $newFileData["type"];
                $newFileName = $newFileData["filename"];

                $oldFile = ModelFile::getFilePath($userId, $modelId, $oldFileType, $oldFileName);
                $newFileDir = ModelFile::getFileTypePath($userId, $modelId, $newFileType);
                $newFile = ModelFile::getFilePath($userId, $modelId, $newFileType, $newFileName);

                $updated = false;

                if ($oldFile != $newFile) {
                    if (file_exists($newFile)) {
                        $response->getBody()->write((new ServerMessage(
                            "Target path already exists.",
                            "TargetAlreadyExists",
                            additional_information: [$oldFileType, $newFileType, $oldFileName, $newFileName]
                        ))->toJson());
                        return $response->withStatus(409);
                    } else {
                        $failed = false;

                        if (!is_dir($newFileDir)) { // Not a directory → Try to create directory
                            // If mkdir fails → returns false → $failed===true
                            $failed = !mkdir($newFileDir, 0755, true);
                        }

                        // Either mkdir already failed or rename failed (returns false on failure)
                        $failed = $failed || !rename($oldFile, $newFile);

                        if ($failed) {
                            $response->getBody()->write((new ServerMessage(
                                "Could not move file.",
                                "CouldNotMoveFile",
                                additional_information: [$oldFileType, $newFileType, $oldFileName, $newFileName]
                            ))->toJson());
                            return $response->withStatus(409);
                        }
                    }

                    $updated = true;
                }

                $updated = $updated || ($oldPosition != $newPosition);

                if ($updated) {
                    DB::connection()
                        ->update([
                            "type" => $newFileType,
                            "filename" => $newFileName,
                            "position" => $newPosition,
                            "updated_at" => time()
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

    public function downloadZipFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $type = $args["type"];

        $basePath = ModelFile::getFileBasePath($userId, $modelId) . (($type == "all") ? "" : "$type/");

        if (is_dir($basePath)) {
            $zipPath = "../upload_temp/zips/";
            if (!is_dir($zipPath)) mkdir($zipPath, 0755, true);
            $zipFilePath = "$zipPath{$userId}_$modelId.zip";
            if (file_exists($zipFilePath)) unlink($zipFilePath);

            $zip = new ZipFile();
            try {
                $zip->addDirRecursive($basePath)
                    ->saveAsFile($zipFilePath);
            } catch (ZipException) {
                return $response->withStatus(500);
            }

            $zip->close();
            $fileStream = new LazyOpenStream($zipFilePath, "r");
            return $response->withBody($fileStream)->withHeader("Content-Type", "application/zip");
        }

        return $response->withStatus(404);
    }

    public function saveFile(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $body = $request->getParsedBody();
        $filename = $body["filename"];
        $type = $body["type"];

        // Check whether the same file exists already
        $targetFilePath = ModelFile::getFilePath($userId, $modelId, $type, $filename);
        if (file_exists($targetFilePath)) {
            $response->getBody()->write((new ServerMessage(
                "Target path already exists.",
                "TargetAlreadyExists"
            ))->toJson());
            return $response->withStatus(409);
        } else { // File doesn't exist
            $time = $body["timestamp"];
            $chunkPath = "../upload_temp/chunked/$userId/$time/";
            if (!is_dir($chunkPath)) mkdir($chunkPath, 0755, true);

            $chunk = $body["chunk"];
            $total = $body["totalChunks"];

            $file = $request->getUploadedFiles()["file"];
            $chunkName = "{$chunk}__$filename";
            $file->moveTo($chunkPath . $chunkName);

            if (($chunk + 1) == $total) {
                $targetFile = fopen($targetFilePath, "x");

                for ($i = 0; $i < $total; $i++) {
                    $chunkFilePath = "$chunkPath/{$i}__$filename";
                    $chunkFile = fopen($chunkFilePath, "r");
                    fwrite($targetFile, fread($chunkFile, filesize($chunkFilePath)));
                    fclose($chunkFile);
                    unlink($chunkFilePath);
                }

                fclose($targetFile);

                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, filesize($targetFilePath));
            }
        }

        FileSystem::RemoveEmptySubFolders("../upload_temp/chunked/");
        return $response;
    }
}
