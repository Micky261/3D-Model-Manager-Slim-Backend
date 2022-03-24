<?php

namespace App\ThreeDModels\Importer;

use App\Utils\Configuration;
use Exception;

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

    /**
     * @throws Exception If importer not found
     */
    public static function getImporter(string $importer): BaseImporter|null {
        return match ($importer) {
            "thingiverse" => new ThingiverseImporter(),
            "myminifactory" => new MyMiniFactoryImporter(),
            default => null
        };
    }

    public abstract function import(array $args): string;
}
