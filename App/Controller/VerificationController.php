<?php

namespace App\Controller;

use App\Models\ServerMessage;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VerificationController {
    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request, Response $response, $args): Response {
        $email = $args["email"];

        $user = DB::connection()->select()
            ->from("users")
            ->where(new Conditional("email", "=", $email))
            ->execute()
            ->fetch();

        if ($user !== false) {
            if ($user["email_verified_at"] == null) {

                $response->getBody()->write((new ServerMessage('Already verified', 'ALREADY_VERIFIED'))->toJson());
                return $response;

            }

            // TODO: Implement sending of mail

            $response->getBody()->write((new ServerMessage('Success', 'SUCCESS'))->toJson());
            return $response;
        }

        $response->getBody()->write(ServerMessage::unknownError(VerificationController::class, __LINE__)->toJson());
        return $response->withStatus(404);
    }


    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(Request $request, Response $response, $args): Response {
        $email = $args["email"];

        $user = DB::connection()->select()
            ->from("users")
            ->where(new Conditional("email", "=", $email))
            ->execute()
            ->fetch();

        if ($user !== false) {
            if ($user["email_verified_at"] == null) {

                $response->getBody()->write((new ServerMessage('Already verified', 'ALREADY_VERIFIED'))->toJson());
                return $response;
            }

            // TODO: Check verification
            // TODO: Update user
        }

        $response->getBody()->write(ServerMessage::unknownError(VerificationController::class, __LINE__)->toJson());
        return $response->withStatus(404);

    }
}
