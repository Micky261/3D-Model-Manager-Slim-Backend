<?php

namespace App\Controller;

use App\Models\Model;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use FaaPz\PDO\Clause\Limit;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelController {
    public function getAllModels(Request $request, Response $response): Response {
        $userId = $request->getAttribute("sessionUserId");

        $models = DB::connection()->select()->from("models")
            ->where(new Conditional("user_id", "=", $userId))
            ->execute()->fetchAll();

        $response->getBody()->write(json_encode($models));
        return $response;
    }

    public function createModel(Request $request, Response $response): Response {
        $userId = $request->getAttribute("sessionUserId");
        $body = $request->getParsedBody();

        $modelId = Model::createModel($userId, $body["name"], $body["links"], $body["description"], $body["notes"], $body["favorite"], $body["author"], $body["licence"]);

        $model = Model::getModel($userId, $modelId);
        $model["links"] = is_null($model["links"]) ? array() : json_decode($model["links"]);

        $response->getBody()->write(json_encode($model, true));
        return $response;
    }

    public function getModel(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $model = Model::getModel($userId, $modelId);
        if ($model !== false) {
            $model["links"] = json_decode($model["links"]);

            $response->getBody()->write(json_encode($model));
            return $response;
        }

        return $response->withStatus(404);
    }

    public function updateModel(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $body = $request->getParsedBody();

        $model = Model::getModel($userId, $modelId);
        if ($model !== false) {
            DB::connection()
                ->update([
                    "name" => $body["name"],
                    "links" => json_encode($body["links"]),
                    "description" => $body["description"],
                    "notes" => $body["notes"],
                    "favorite" => DB::boolValue($body["favorite"]),
                    "author" => $body["author"],
                    "licence" => $body["licence"]
                ])
                ->table("models")
                ->where(new Grouping(
                    "AND",
                    new Conditional("id", "=", $modelId),
                    new Conditional("user_id", "=", $userId)
                ))
                ->execute();

            $model = Model::getModel($userId, $modelId);
            $model["links"] = json_decode($model["links"]);

            $response->getBody()->write(json_encode($model));
            return $response;
        }

        return $response->withStatus(404);
    }

    public function deleteModel(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $model = Model::getModel($userId, $modelId);
        if ($model !== false) {
            $model["links"] = json_decode($model["links"]);

            DB::connection()
                ->delete()
                ->from("models")
                ->where(new Grouping(
                    "AND",
                    new Conditional("id", "=", $modelId),
                    new Conditional("user_id", "=", $userId)
                ))
                ->execute();

            $response->getBody()->write(json_encode($model));
            return $response;
        }

        return $response->withStatus(404);
    }

    public function getRandomModels(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $num = $args["num"];

        if ($num > 0) {
            $models = DB::connection()->select()->from("models")
                ->where(new Conditional("user_id", "=", $userId))
                ->orderBy("RAND()", "ASC") // TODO: Remove "ASC", should be possible with faapz pdo 2.2.1
                ->limit(new Limit($num))
                ->execute()->fetchAll();

            $response->getBody()->write(json_encode($models));
            return $response;
        }

        return $response->withStatus(400);
    }

    public function getNewestModels(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $num = $args["num"];

        if ($num > 0) {
            $models = DB::connection()->select()->from("models")
                ->where(new Conditional("user_id", "=", $userId))
                ->orderBy("id", "DESC")
                ->limit(new Limit($num))
                ->execute()->fetchAll();

            $response->getBody()->write(json_encode($models));
            return $response;
        }

        return $response->withStatus(400);
    }
}
