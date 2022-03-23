<?php

namespace App\Middleware;

use App\Models\ServerMessage;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class AuthMiddleware {
    public function __invoke(Request $request, RequestHandler $handler): Response {
        /**
         * if (key_exists("3DMM_Session", $_COOKIE)) {
         *     $sessionId = htmlspecialchars($_COOKIE["3DMM_Session"]);
         **/

        $sessionId = $request->getQueryParams()["3DMM_Session"];
        if (isset($sessionId)) {
            $sessionId = htmlspecialchars($sessionId);

            $sessionQuery = DB::connection()->select()->from("sessions")->where(
                new Grouping(
                    "AND",
                    new Conditional("session_id", "=", $sessionId),
                    new Conditional("created_at", ">", time() - 60 * 60 * 24 * 28) // Session only valid for 28 days
                )
            );

            if ($stmt = $sessionQuery->execute()) {
                $session = $stmt->fetch(); // session_id is unique

                if (!empty($session)) {
                    return $handler->handle(
                        $request->withAttribute("session", $session)
                            ->withAttribute("sessionUserId", $session["user_id"])
                    );
                }
            }
        }

        // Path param missing or no session found
        $r = new Response();
        $r->getBody()->write((new ServerMessage("Error on auth", "AUTH_ERROR"))->toJson());
        return $r->withStatus(405)->withHeader("Content-Type", "application/json");
    }
}
