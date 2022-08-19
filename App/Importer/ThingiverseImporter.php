<?php

namespace App\Importer;

use App\Api\Thingiverse\Thingiverse;
use App\Models\Model;
use App\Models\ModelTag;
use App\Storage\FileSystem;
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

    public function __destruct() {
        FileSystem::removeTempFolders();
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
        foreach ($images as $pos => $image) {
            $filename = $image->name;

            foreach ($image->sizes as $imageSize) {
                if ($imageSize->type === "display" && $imageSize->size === "large") {
                    if (count(explode(".", $filename)) == 1) {
                        $filename .= "." . pathinfo($imageSize->url, PATHINFO_EXTENSION);
                    }
                    self::storeFile($imageSize->url, $userId, $modelId, $type, $filename, $pos + 1);
                }
            }
        }

        /**
         * Get thing files and import them
         */
        $type = "model";
        $thingiverse->getThingFiles($id);
        $files = $thingiverse->response_data;
//        error_log(var_export($files, true), 3, "error.log");
        foreach ($files as $pos => $file) {
            self::storeFile($file->public_url, $userId, $modelId, $type, $file->name, $pos + 1);
        }

        return $modelId;
    }
}
