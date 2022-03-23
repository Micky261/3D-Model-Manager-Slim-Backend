<?php

namespace App\Middleware;

use App\Utils\Configuration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CORSMiddleware {
    public function __invoke(Request $request, RequestHandler $handler): Response {
        $response = $handler->handle($request);

        if (Configuration::general()["enable-cors"]) {
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*') // TODO: Get domain from config
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');//->withHeader('Access-Control-Allow-Credentials', 'true');
        } else {
            return $response;
        }
    }
}
