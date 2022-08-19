<?php

namespace App\Importer;

use App\Api\Printables\Printables;
use App\Models\Model;
use App\Models\ModelTag;
use App\Storage\FileSystem;
use Exception;
use League\HTMLToMarkdown\HtmlConverter;

class PrintablesImporter extends BaseImporter {
    const MODEL_BASE_URL = "https://www.printables.com/model/";
    const MEDIA_URL = "https://media.printables.com/";

    private HtmlConverter $htmlConverter;

    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        if (!BaseImporter::isEnabled("printables")) {
            throw new Exception("Printables importer is disabled");
        }

        $this->htmlConverter = self::getHtmlConvert();
    }

    public function __destruct() {
        FileSystem::removeTempFolders();
    }

    public function import(int $userId, array $args): string {
        $id = $args["id"];
        $printables = new Printables();

        /**
         * Get object data and create model
         */
        $object = $printables->getObject($id)->data->print;
        $modelId = Model::createModel(
            $userId,
            $object->name,
            [],
            "Summary: " . $object->summary . "\n\n" . $this->htmlConverter->convert($object->description),
            "",
            "",
            $object->user->publicUsername,
            $object->license->name,
            $object->name,
            "Summary: " . $object->summary . "<br />\n<br />\n" . $object->description,
            $object->user->publicUsername,
            $object->license->name,
            self::MODEL_BASE_URL . $id
        );

        /**
         * Get tags
         */
        // Uses the $object object
        foreach ($object->tags as $tag) {
            ModelTag::createTag($userId, $modelId, $tag->name);
        }

        /**
         * Get thing images and import them
         */
        $type = "image";
        foreach ($object->images as $pos => $image) {
            $filename = basename($image->filePath);
            self::storeFile(self::MEDIA_URL . $image->filePath, $userId, $modelId, $type, $filename, $pos + 1);
        }
        $lastImagePosition = ((isset($pos) && is_int($pos)) ? $pos : 0) + 1;
        $previewImages = array_merge($object->gcodes, $object->stls, $object->slas);
        foreach ($previewImages as $pos => $image) {
            $filename = basename($image->filePreviewPath);
            self::storeFile(self::MEDIA_URL . $image->filePreviewPath, $userId, $modelId, $type, $filename, $lastImagePosition + $pos + 1);
        }

        /**
         * Get pdf file and import it
         */
        $type = "document";
        $filename = basename($object->pdfFilePath);
        self::storeFile(self::MEDIA_URL . $object->pdfFilePath, $userId, $modelId, $type, $filename, 1);

        /**
         * Get stl files and import them
         */
        $type = "model";
        foreach ($object->stls as $pos => $file) {
            self::storeFile(self::MEDIA_URL . $file->filePath, $userId, $modelId, $type, $file->name, $pos + 1);
        }

        /**
         * Get gcode and sla files and import them
         */
        $type = "sliced";
        $sliced_files = array_merge($object->gcodes, $object->slas);
        foreach ($sliced_files as $pos => $file) {
            self::storeFile(self::MEDIA_URL . $file->filePath, $userId, $modelId, $type, $file->name, $pos + 1);
        }

        return $modelId;
    }
}
