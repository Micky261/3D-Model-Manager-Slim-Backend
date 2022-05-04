<?php

namespace App\Controller;

use App\Models\Model;
use App\Models\ServerMessage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SearchController {
    public function searchModels(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $searchTerm = $args["search-term"];
        $searchFields = explode("\x1F", $args["fields"]);

        if (count($searchFields) > 0) {
            $response->getBody()->write(json_encode(Model::searchModels($userId, $searchTerm, $searchFields)));
        } else {
            $response->getBody()->write((new ServerMessage("Error searching", "SEARCH_ERROR_NO_FIELDS"))->toJson());
        }

        return $response;
    }
}
