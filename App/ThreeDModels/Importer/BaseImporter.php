<?php

namespace App\ThreeDModels\Importer;

use App\Utils\Configuration;

class BaseImporter {
    function getEnabledImporters(): array {
        $importer_config = Configuration::importer();
        $enabledImporters = array();

        foreach ($importer_config as $importer => $values) {
            if ($values["enabled"]) {
                $enabledImporters[] = $importer;
            }
        }

        return $enabledImporters;
    }
}
