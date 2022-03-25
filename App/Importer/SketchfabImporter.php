<?php

namespace App\Importer;

use App\Api\Sketchfab\Sketchfab;
use App\Models\Model;
use App\Models\ModelFile;
use App\Models\ModelTag;
use App\Utils\Configuration;
use Exception;

class SketchfabImporter extends BaseImporter {
    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        if (!BaseImporter::isEnabled("sketchfab")) {
            throw new Exception("Sketchfab importer is disabled");
        }
    }

    public function import(int $userId, array $args): string {
        $id = $args["id"];

        $personalApiKey = Configuration::importer()["sketchfab"]["api-token"];
        $personalApiKeyAvailable = $personalApiKey != "";

        if ($personalApiKeyAvailable) {
            $sketchfab = new Sketchfab($personalApiKey);
        } else {
            $sketchfab = new Sketchfab();
        }

        /**
         * Get model data and create model
         */
        $model = $sketchfab->getModel($id);

        $modelId = Model::createModel(
            $userId,
            $model->name,
            [],
            $model->description,
            "",
            "",
            $model->user->displayName,
            $model->license->fullName,
            $model->name,
            $model->description,
            $model->user->displayName,
            $model->license->fullName,
            $model->viewerUrl
        );

        /**
         * Get tags
         */
        // Uses the $model object
        foreach ($model->tags as $tag) {
            ModelTag::createTag($userId, $modelId, $tag->name);
        }

        /**
         * Get thing images and import them
         */
        $type = "image";
//        foreach ($model->images as $position => $image) {
        $filename = basename($model->thumbnails->images[0]->url);
        $path = ModelFile::getFileTypePath($userId, $modelId, $type);

        $size = ModelFile::moveFileOnDisk($model->thumbnails->images[0]->url, $path, $filename);

        if ($size != false) {
            ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, 1);
        }
//        }

        /**
         * Get thing files and import them
         */
        if ($model->isDownloadable && $personalApiKeyAvailable) {
            $files = $sketchfab->getModelFiles($id);

            $type = "model";
            $position = 1;
            foreach ($files as $file) {
                $filename = basename(parse_url($file->url, PHP_URL_PATH));
                $path = ModelFile::getFileTypePath($userId, $modelId, $type);

                $size = ModelFile::moveFileOnDisk($file->url, $path, $filename);
                if ($size != false) {
                    ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position);
                    $position++;
                }
            }
        }

        return $modelId;
    }
}
