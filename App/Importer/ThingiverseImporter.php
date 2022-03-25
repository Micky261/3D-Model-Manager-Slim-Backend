<?php

namespace App\Importer;

use App\Api\Thingiverse\Thingiverse;
use App\Models\Model;
use App\Models\ModelFile;
use App\Models\ModelTag;
use App\Utils\Configuration;
use Exception;

class ThingiverseImporter extends BaseImporter {
    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        if (!BaseImporter::isEnabled("thingiverse")) {
            throw new Exception("Thingiverse importer is disabled");
        }
    }

    public function import(int $userId, array $args): string {
        $id = $args["id"];

        $thingiverse = new Thingiverse(Configuration::importer()["thingiverse"]["app-token"]);

        /**
         * Get thing data and create model
         */
        $thingiverse->getThing($id);
        $thing = $thingiverse->response_data;

        $modelId = Model::createModel(
            $userId, $thing->name, [], $thing->description . "\n\n" . $thing->instructions, "", "", $thing->creator->name, $thing->license,
            $thing->name, $thing->details, $thing->creator->name, $thing->license, $thing->public_url
        );

        /**
         * Get tags
         */
        // Uses the $thing object
        foreach ($thing->tags as $tag) {
            ModelTag::createTag($userId, $modelId, $tag->name);
        }

        /**
         * Get thing images and import them
         */
        $type = "image";
        $thingiverse->getThingImages($id);
        $images = $thingiverse->response_data;

        foreach ($images as $position => $image) {
            $filename = $image->name;
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            foreach ($image->sizes as $imageSize) {
                if ($imageSize->type === "display" && $imageSize->size === "large") {
                    $size = ModelFile::moveFileOnDisk($imageSize->url, $path, $filename);

                    if ($size != false) {
                        ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position + 1);
                    }
                }
            }
        }

        /**
         * Get thing files and import them
         */
        $type = "model";
        $thingiverse->getThingFiles($id);
        $files = $thingiverse->response_data;

        foreach ($files as $position => $file) {
            $filename = $file->name;
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            $size = ModelFile::moveFileOnDisk($file->direct_url, $path, $filename);
            if ($size != false) {
                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position + 1);
            }
        }

        return $modelId;
    }
}
