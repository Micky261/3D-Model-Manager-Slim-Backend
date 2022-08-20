<?php

namespace App\Importer;

use App\Api\Instructables\Instructables;
use App\Models\FileType;
use App\Models\Model;
use App\Storage\FileSystem;
use Exception;

class InstructablesImporter extends BaseImporter {
    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        if (!BaseImporter::isEnabled("instructables")) {
            throw new Exception("Instructables importer is disabled");
        }
    }

    public function import(int $userId, array $args): string {
        $slug = $args["id"];

        $instructables = new Instructables();

        /**
         * Get object data and create model
         */
        $files = $instructables->getObject($slug)->files;

        $modelId = Model::createModel(
            $userId,
            $slug,
            [],
            "",
            "",
            "",
            "",
            ""
        );

        /**
         * Get files import them
         */
        $imagePosition = 1;
        $diagramPosition = 1;
        $documentPosition = 1;
        $modelPosition = 1;
        $slicedPosition = 1;
        foreach ($files as $filesForStep) {
            foreach ($filesForStep as $file) {
                $url = $file->downloadUrl;
                $filename = $file->name;

                if ($file->image) {
                    self::storeFile($url, $userId, $modelId, "image", $filename, $imagePosition);
                    $imagePosition++;
                } else {
                    $type = FileType::getModelFileTypeFromFilename($filename);

                    switch ($type) {
                        case "diagram":
                            self::storeFile($url, $userId, $modelId, $type, $filename, $diagramPosition);
                            $diagramPosition++;
                            break;
                        case "document":
                            self::storeFile($url, $userId, $modelId, $type, $filename, $documentPosition);
                            $documentPosition++;
                            break;
                        case "model":
                            self::storeFile($url, $userId, $modelId, $type, $filename, $modelPosition);
                            $modelPosition++;
                            break;
                        case "sliced":
                            self::storeFile($url, $userId, $modelId, $type, $filename, $slicedPosition);
                            $slicedPosition++;
                            break;
                        default:
                            break;
                    }

                    $coverImg = $file->embedCoverImage;
                    if ($coverImg->image) {
                        self::storeFile($coverImg->downloadUrl, $userId, $modelId, "image", $coverImg->name, $imagePosition);
                        $imagePosition++;
                    }
                }

            }
        }

        return $modelId;
    }

    public function __destruct() {
        FileSystem::removeTempFolders();
    }
}
