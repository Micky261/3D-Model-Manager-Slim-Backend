<?php

namespace App\Controller;

use App\Models\ServerMessage;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController {
    // TODO: Delete session on logout

    public function register(Request $request) {
        // $body = $request->getParsedBody();
        // TODO
        /**if (User::where("email", $request->email)->exists()) {
         * return response()->json(new ServerMessage([
         * "message" => "User already exists.",
         * "message_code" => "USER_ALREADY_EXISTS"
         * ]), 409);
         * }
         *
         * $request->validate([
         * "name" => " required",
         * "email" => "email | required",
         * "password" => "required"
         * ]);
         *
         * $user = new User();
         * $user->name = $request->name;
         * $user->email = $request->email;
         * $user->password = Hash::make($request->password);
         *
         * $user->save();
         *
         * event(new Registered($user));
         *
         * return response()->json(new ServerMessage([
         * "message" => "Success",
         * "message_code" => "SUCCESS"
         * ]));**/
    }

    public function login(Request $request, Response $response): Response {
        $body = $request->getParsedBody();

        $db = DB::connection();
        $userQuery = $db->select()->from("users")->where(new Conditional("email", "=", $body["email"]));

        if ($stmt = $userQuery->execute()) {
            $user = $stmt->fetch(); // email is unique

            if (password_verify($body["password"], $user["password"])) {
                $sessionId = DB::generateSessionId(128);

                $insertSession = $db->insert(["user_id", "session_id"])
                    ->into("sessions")
                    ->values($user["id"], $sessionId);

                if ($insertSession->execute()) {
                    /**
                     * setcookie("3DMM_Session", $sessionId, [
                     * 'Expires' => time() + 60 * 60 * 24 * 28 - 60,
                     * 'Secure' => true,
                     * 'SameSite' => 'None',
                     * 'Path' => "/",
                     * "Domain"=>"localhost:8000"
                     * ]);
                     **/

                    $response->getBody()->write(json_encode(["session_id" => $sessionId, "session_expiry" => time() + 60 * 60 * 24 * 28 - 60]));
                    return $response;
                }
            }
        }

        $response->getBody()->write((new ServerMessage("Error on login", "LOGIN_ERROR"))->toJson());
        return $response->withStatus(405);
    }
}
