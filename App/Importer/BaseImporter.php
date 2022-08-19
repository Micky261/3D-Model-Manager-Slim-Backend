<?php

namespace App\Importer;

use App\Models\ModelFile;
use App\Storage\Storage;
use App\Utils\Configuration;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

abstract class BaseImporter {
    public static function isEnabled(string $importer): bool {
        return in_array($importer, BaseImporter::getEnabledImporters());
    }

    public static function getEnabledImporters(): array {
        $importer_config = Configuration::importer();
        $enabledImporters = array();

        foreach ($importer_config as $importer => $values) {
            if ($values["enabled"]) {
                $enabledImporters[] = $importer;
            }
        }

        return $enabledImporters;
    }

    public static function getImporter(string $importer): BaseImporter|null {
        return match ($importer) {
            "thingiverse" => new ThingiverseImporter(),
            "myminifactory" => new MyMiniFactoryImporter(),
            "sketchfab" => new SketchfabImporter(),
            "printables" => new PrintablesImporter(),
            default => null
        };
    }

    /**
     * Downloads file and moves it to a temporary location
     *
     * @param string $downloadUrl The url where to download the image from
     * @param int    $userId
     * @param int    $modelId
     * @param string $type
     * @param string $filename    The filename which will be stored
     * @param int    $position    1-based file position within its type
     * @return void
     */
    public static function storeFile(string $downloadUrl, int $userId, int $modelId, string $type, string $filename, int $position): void {
        $time = time();
        $tempPath = Storage::$temporaryStorageBasePath . "/import/$userId/$time/";
        $size = ModelFile::moveFileOnDisk($downloadUrl, $tempPath, $filename);

        if ($size) {
            $storage = Storage::getRandomStorageClass($size);
            $targetFilePath = $storage->getFileTypePath($userId, $modelId, $type);

            $storage->uploadFile($tempPath . $filename, $targetFilePath, $filename);
            ModelFile::createFileDBEntry($userId, $modelId, $storage->storage->name, $type, $filename, $size, $position);
        }
    }

    public static function getHtmlConvert(): HtmlConverter {
        $htmlConverter = new HtmlConverter();
        $htmlConverter->getConfig()->setOption('strip_tags', true);
        $htmlConverter->getConfig()->setOption('italic_style', '*');
        $htmlConverter->getConfig()->setOption('bold_style', '**');
        $htmlConverter->getConfig()->setOption('hard_break', true);
        $htmlConverter->getEnvironment()->addConverter(new TableConverter());

        return $htmlConverter;
    }

    public abstract function __destruct();

    public abstract function import(int $userId, array $args): string;
}
