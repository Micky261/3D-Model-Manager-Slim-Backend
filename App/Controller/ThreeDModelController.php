<?php

namespace App\Controller;

use App\Models\ServerMessage;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use FaaPz\PDO\Clause\Limit;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ThreeDModelController {
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

        $createQuery = DB::connection()
            ->insert(
                ["user_id", "name", "links", "description", "notes", "favorite", "author", "licence"]
            )
            ->into("models")
            ->values(
                $userId,
                $body["name"],
                json_encode($body["links"], true),
                $body["description"],
                $body["notes"],
                var_export($body["favorite"], true),
                $body["author"],
                $body["licence"]
            );

        if ($createQuery->execute()) {
            $modelId = DB::connection()->lastInsertId();
            $model = $this->selectModel($userId, $modelId);
            $model["links"] = json_decode($model["links"]);

            $response->getBody()->write(json_encode($model, true));
            return $response;
        }

        $response->getBody()->write(ServerMessage::unknownError(ThreeDModelController::class, __LINE__)->toJson());
        return $response;
    }

    private function selectModel(int $userId, int $modelId) {
        return DB::connection()
            ->select()
            ->from("models")
            ->where(
                new Grouping(
                    "AND",
                    new Conditional("id", "=", $modelId),
                    new Conditional("user_id", "=", $userId)
                )
            )
            ->execute()
            ->fetch();
    }

    public function importModel(Request $request, Response $response): Response {
        // $userId = $request->getAttribute("sessionUserId");
        $body = $request->getParsedBody();

        $response->getBody()->write($body);
        return $response;
    }

    public function getModel(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $model = $this->selectModel($userId, $modelId);
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

        $model = $this->selectModel($userId, $modelId);
        if ($model !== false) {
            DB::connection()
                ->update([
                    "name" => $body["name"],
                    "links" => json_encode($body["links"]),
                    "description" => $body["description"],
                    "notes" => $body["notes"],
                    "favorite" => var_export($body["favorite"], true),
                    "author" => $body["author"],
                    "licence" => $body["licence"],
                    "updated_at" => time()
                ])
                ->table("models")
                ->where(new Grouping(
                    "AND",
                    new Conditional("id", "=", $modelId),
                    new Conditional("user_id", "=", $userId)
                ))
                ->execute();

            $model = $this->selectModel($userId, $modelId);
            $model["links"] = json_decode($model["links"]);

            $response->getBody()->write(json_encode($model));
            return $response;
        }

        return $response->withStatus(404);
    }

    public function deleteModel(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $model = $this->selectModel($userId, $modelId);
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

            $model = $this->selectModel($userId, $modelId);
            $model["links"] = json_decode($model["links"]);

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
