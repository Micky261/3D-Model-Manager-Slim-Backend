<?php

namespace App\ThreeDModels\Importer;

use App\Utils\Configuration;
use Exception;

class ThingiverseImporter extends BaseImporter {
    private static string $baseUrl = "https://www.thingiverse.com/thing:%d/zip";

    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        if (!BaseImporter::isEnabled("thingiverse")) {
            throw new Exception("Thingiverse importer is disabled");
        }
    }

    public function import(int $userId, array $args): string {
        $id = $args["id"];

        return;
    }
}
