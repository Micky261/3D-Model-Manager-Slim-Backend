<?php

namespace App\Controller;

use App\Models\Model;
use App\Models\ServerMessage;
use App\Importer\BaseImporter;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ImportController {
    public function getEnabledImporters(Request $request, Response $response, $args): Response {
        $response->getBody()->write(json_encode(BaseImporter::getEnabledImporters()));
        return $response;
    }

    /**
     * @throws Exception
     */
    public function import(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $importer = $args["importer"];

        $imp = BaseImporter::getImporter($importer);

        if ($imp != null) {
            $modelId = $imp->import($userId, $request->getParsedBody());

            $model = Model::getModel($userId, $modelId);
            $model["links"] = json_decode($model["links"]);

            $response->getBody()->write(json_encode($model));
            return $response;
        } else {
            $response->getBody()->write((new ServerMessage("Error on auth", "AUTH_ERROR_EMAIL_VERIFICATION"))->toJson());
            return $response->withStatus(405);
        }
    }
}
