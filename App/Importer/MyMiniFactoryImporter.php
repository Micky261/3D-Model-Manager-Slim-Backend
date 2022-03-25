<?php

namespace App\Importer;

use App\Api\MyMiniFactory\MyMiniFactory;
use App\Models\Model;
use App\Models\ModelFile;
use App\Models\ModelTag;
use App\Utils\Configuration;
use Exception;
use League\HTMLToMarkdown\Converter\TableConverter;
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

        $this->htmlConverter = new HtmlConverter();
        $this->htmlConverter->getConfig()->setOption('strip_tags', true);
        $this->htmlConverter->getConfig()->setOption('italic_style', '*');
        $this->htmlConverter->getConfig()->setOption('bold_style', '**');
        $this->htmlConverter->getConfig()->setOption('hard_break', true);
        $this->htmlConverter->getEnvironment()->addConverter(new TableConverter());
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
        foreach ($object->images as $position => $image) {
            $filename = basename($image->original->url);
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            $size = ModelFile::moveFileOnDisk($image->original->url, $path, $filename);

            if ($size != false) {
                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position + 1);
            }
        }

        /**
         * Get thing files and import them
         */
        $type = "model";
        foreach ($object->files->items as $position => $file) {
            $filename = $file->filename;
            $path = ModelFile::getFileTypePath($userId, $modelId, $type);

            $download = sprintf($this->downloadFile, $id, $filename);
            $size = ModelFile::moveFileOnDisk($download, $path, $filename);
            if ($size != false) {
                ModelFile::createFileDBEntry($userId, $modelId, $type, $filename, $size, $position + 1);
            }
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
