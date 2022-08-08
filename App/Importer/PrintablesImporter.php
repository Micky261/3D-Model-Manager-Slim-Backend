<?php

namespace App\Importer;

use App\Api\Printables\Printables;
use App\Models\Model;
use App\Models\ModelFile;
use App\Models\ModelTag;
use Exception;
use League\HTMLToMarkdown\Converter\TableConverter;
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

        $this->htmlConverter = new HtmlConverter();
        $this->htmlConverter->getConfig()->setOption('strip_tags', true);
        $this->htmlConverter->getConfig()->setOption('italic_style', '*');
        $this->htmlConverter->getConfig()->setOption('bold_style', '**');
        $this->htmlConverter->getConfig()->setOption('hard_break', true);
        $this->htmlConverter->getEnvironment()->addConverter(new TableConverter());
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
        foreach ($object->images as $position => $image) {
            $filename = basename($image->filePath);
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            $size = ModelFile::moveFileOnDisk(self::MEDIA_URL . $image->filePath, $path, $filename);

            if ($size != false) {
                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position + 1);
            }
        }
        $lastImagePosition = ((isset($position) && is_int($position)) ? $position : 0) + 1;
        $previewImages = array_merge($object->gcodes, $object->stls, $object->slas);
        foreach ($previewImages as $position => $image) {
            $filename = basename($image->filePreviewPath);
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            $size = ModelFile::moveFileOnDisk(self::MEDIA_URL . $image->filePreviewPath, $path, $filename);

            if ($size != false) {
                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $lastImagePosition + $position + 1);
            }
        }

        /**
         * Get pdf file and import it
         */
        $type = "document";
        $filename = basename($object->pdfFilePath);
        $path = ModelFile::getFileTypePath($userId, $modelId, $type);

        $size = ModelFile::moveFileOnDisk(self::MEDIA_URL . $object->pdfFilePath, $path, $filename);
        if ($size != false) {
            ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, 1);
        }

        /**
         * Get stl files and import them
         */
        $type = "model";
        foreach ($object->stls as $position => $file) {
            $filename = $file->name;
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            $size = ModelFile::moveFileOnDisk(self::MEDIA_URL . $file->filePath, $path, $filename);
            if ($size != false) {
                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position + 1);
            }
        }

        /**
         * Get gcode and sla files and import them
         */
        $type = "sliced";
        $sliced_files = array_merge($object->gcodes, $object->slas);
        foreach ($sliced_files as $position => $file) {
            $filename = $file->name;
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            $size = ModelFile::moveFileOnDisk(self::MEDIA_URL . $file->filePath, $path, $filename);
            if ($size != false) {
                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position + 1);
            }
        }

        return $modelId;
    }
}
