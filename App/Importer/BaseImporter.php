<?php

namespace App\Importer;

use App\Utils\Configuration;

abstract class BaseImporter {
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

    public static function isEnabled(string $importer): bool {
        return in_array($importer, BaseImporter::getEnabledImporters());
    }

    public static function getImporter(string $importer): BaseImporter|null {
        return match ($importer) {
            "thingiverse" => new ThingiverseImporter(),
            "myminifactory" => new MyMiniFactoryImporter(),
            "sketchfab" => new SketchfabImporter(),
            default => null
        };
    }

    public abstract function import(int $userId, array $args): string;
}
