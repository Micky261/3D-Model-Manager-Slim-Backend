<?php

namespace App\Importer;

use App\Api\Cults3D\Cults3D;
use App\Models\Model;
use App\Models\ModelTag;
use App\Storage\FileSystem;
use App\Utils\Configuration;
use Exception;
use League\HTMLToMarkdown\HtmlConverter;

class Cults3DImporter extends BaseImporter {
    private HtmlConverter $htmlConverter;

    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        if (!BaseImporter::isEnabled("cults3d")) {
            throw new Exception("Cults3D importer is disabled");
        }

        $this->htmlConverter = self::getHtmlConvert();
    }

    public function import(int $userId, array $args): string {
        $slug = $args["id"];

        $config = Configuration::importer()["cults3d"];
        $cults3d = new Cults3D($config["username"], $config["password"]);

        /**
         * Get object data and create model
         */
        $object = $cults3d->getObject($slug)->data->creation;

        $objLicence = $object->license->name;
        $licence = ($objLicence == null) ? "All Rights Reserved" : $objLicence;

        $modelId = Model::createModel(
            $userId,
            $object->name,
            [],
            $this->htmlConverter->convert($object->description),
            "",
            "",
            $object->creator->nick,
            $licence,
            $object->name,
            $object->description,
            $object->creator->nick,
            $licence,
            $object->url
        );

        /**
         * Get tags
         */
        foreach ($object->tags as $tag) {
            ModelTag::createTag($userId, $modelId, $tag);
        }

        /**
         * Get thing images and import them
         */
        $type = "image";
        foreach ($object->illustrations as $pos => $image) {
            $url = $this->getImageUrl($image->imageUrl);
            $filename = basename($url);

            self::storeFile($url, $userId, $modelId, $type, $filename, $pos + 1);
        }
        $lastImagePosition = ((isset($pos) && is_int($pos)) ? $pos : 0) + 1;
        foreach ($object->blueprints as $pos => $file) {
            $url = $this->getImageUrl($file->imageUrl) . ".png";
            $filename = basename($url);

            self::storeFile($url, $userId, $modelId, $type, $filename, $lastImagePosition + $pos + 1);
        }

        /**
         * Get thing files and import them
         */
        $type = "model";
        $position = 1;
        foreach ($object->blueprints as $file) {
            $fileUrl = $file->fileUrl;

            if ($fileUrl != null && $fileUrl != "null" && $fileUrl != "") {
                $filename = basename($fileUrl);

                self::storeFile($fileUrl, $userId, $modelId, $type, $filename, $position);

                $position++;
            }
        }

        return $modelId;
    }

    private function getImageUrl(string $sourceUrl): string {
        return substr($sourceUrl, strpos($sourceUrl, "http", 5));
    }

    public function __destruct() {
        FileSystem::removeTempFolders();
    }
}
