<?php

namespace App\Middleware;

use App\Models\ServerMessage;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class VerifiedMiddleware {
    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface {
        $userId = $request->getAttribute("session")["user_id"];

        $userQuery = DB::connection()->select()->from("users")->where(new Conditional("id", "=", $userId));

        if ($stmt = $userQuery->execute()) {
            $user = $stmt->fetchAll();

            if (!empty($user) && count($user) == 1) {
                return $handler->handle($request->withAttribute("user", $user));
            }
        }

        // User not found or E-Mail not verified
        $r = new Response();
        $r->getBody()->write((new ServerMessage("Error on auth", "AUTH_ERROR_EMAIL_VERIFICATION"))->toJson());
        return $r->withStatus(405)->withHeader("Content-Type", "application/json");
    }
}
