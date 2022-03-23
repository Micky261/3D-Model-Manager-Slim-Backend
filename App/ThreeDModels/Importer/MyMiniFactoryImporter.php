<?php

namespace App\ThreeDModels\Importer;

use App\Utils\Configuration;
use Exception;

class MyMiniFactoryImporter extends BaseImporter {
    /**
     * @throws Exception If importer not enabled
     */
    public function __construct() {
        $mmf_config = Configuration::importer()["myminifactory"];

        if ($mmf_config["enabled"]) {
            throw new Exception("MyMiniFactory importer is disabled");
        }
    }

    // TODO
}
