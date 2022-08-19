<?php

namespace App\Importer;

use App\Api\MyMiniFactory\MyMiniFactory;
use App\Models\Model;
use App\Models\ModelTag;
use App\Storage\FileSystem;
use App\Utils\Configuration;
use Exception;
use League\HTMLToMarkdown\HtmlConverter;

class MyMiniFactoryImporter extends BaseImporter {
    private string $downloadFile = "https://www.myminifactory.com/download/%d?downloadfile=%s";
    private HtmlConverter $htmlConverter;

    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        if (!BaseImporter::isEnabled("myminifactory")) {
            throw new Exception("MyMiniFactory importer is disabled");
        }

        $this->htmlConverter = self::getHtmlConvert();
    }

    public function __destruct() {
        FileSystem::removeTempFolders();
    }

    public function import(int $userId, array $args): string {
        $id = $args["id"];

        $myminifactory = new MyMiniFactory(Configuration::importer()["myminifactory"]["api-key"]);

        /**
         * Get object data and create model
         */
        $object = $myminifactory->getObject($id);

        $modelId = Model::createModel(
            $userId,
            $object->name,
            [],
            $this->htmlConverter->convert($object->description_html),
            "",
            "",
            $object->designer->name,
            $object->license,
            $object->name,
            $object->description_html,
            $object->designer->name,
            $object->license,
            $object->url
        );

        /**
         * Get tags
         */
        // Uses the $object object
        foreach ($object->tags as $tag) {
            ModelTag::createTag($userId, $modelId, $tag);
        }

        /**
         * Get thing images and import them
         */
        $type = "image";
        foreach ($object->images as $pos => $image) {
            $url = $image->original->url;
            $filename = basename($url);

            self::storeFile($url, $userId, $modelId, $type, $filename, $pos + 1);
        }

        /**
         * Get thing files and import them
         */
        $type = "model";
        foreach ($object->files->items as $pos => $file) {
            $filename = $file->filename;
            $download = sprintf($this->downloadFile, $id, $filename);

            self::storeFile($download, $userId, $modelId, $type, $filename, $pos + 1);
        }

        return $modelId;
    }

//    For $object->licenses list
//    private function getLicences(array $licenses): string {
//        $list = array();
//
//        foreach ($licenses as $license) {
//            if ($license->value) {
//                $list[] = $license->type;
//            }
//        }
//
//        return join(",", $list);
//    }
}
