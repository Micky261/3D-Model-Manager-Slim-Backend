<?php

use App\Controller\AuthController;
use App\Controller\ImportController;
use App\Controller\ModelFilesController;
use App\Controller\ModelTagsController;
use App\Controller\ModelController;
use App\Controller\VerificationController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CORSMiddleware;
use App\Middleware\VerifiedMiddleware;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Autoload all classes under App\
 */
spl_autoload_register(function ($class_name) {
    if (file_exists(__DIR__ . "/../$class_name.php")) {
        /** @noinspection PhpIncludeInspection */
        require __DIR__ . "/../$class_name.php";
        return true;
    }
    return false;
});

$app = AppFactory::create();

// Catch CORS Options requests
$app->options('/{routes:.+}', function ($response) {
    return $response;
});

$app->get('/version', function ($request, $response) {
    $response->getBody()->write("0.1.0");
    return $response;
});

// API
$app->group("/api", function (RouteCollectorProxy $group) {
    // Login / Register
    $group->post("/login", [AuthController::class, "login"]);
    $group->post("/register", [AuthController::class, "register"]);

    // Register -> email verification
    $group->get("/email/resend", [VerificationController::class, "resend"]);
    $group->get("/email/verify/{id}/{hash}", [VerificationController::class, "verify"]);

    // Endpoints available to logged-in users with verified email addresses
    $group->group("", function (RouteCollectorProxy $group) {
        /**
         * All endpoints handle data for the requesting user, not for all users
         */

        /**
         * Models
         */
        $group->group("/models", function (RouteCollectorProxy $group) {
            // Get all models
            $group->get("", [ModelController::class, "getAllModels"]);
            // Get a list of {num} random models
            $group->get("/random/{num:[0-9]+}", [ModelController::class, "getRandomModels"]);
            // Get a list of the {num} newest models
            $group->get("/newest/{num:[0-9]+}", [ModelController::class, "getNewestModels"]);
        });

        /**
         * Model
         */
        $group->group("/model", function (RouteCollectorProxy $group) {
            // Get data for the specified model
            $group->get("/data/{id:[0-9]+}", [ModelController::class, "getModel"]);
            // Create a model (data via POST)
            $group->post("/data", [ModelController::class, "createModel"]);
            // Update the specified model
            $group->put("/data/{id:[0-9]+}", [ModelController::class, "updateModel"]);
            // Delete specified model
            $group->delete("/{id:[0-9]+}", [ModelController::class, "deleteModel"]);

            /**
             * Tags for Model
             */
            // Get tags assigned to the specified model
            $group->get("/tags/{id:[0-9]+}", [ModelTagsController::class, "getTags"]);
            // Add a tag for the specified model
            $group->post("/tag/{id:[0-9]+}/{tag}", [ModelTagsController::class, "setTag"]);
            // Remove a tag from the specified model
            $group->delete("/tag/{id:[0-9]+}/{tag}", [ModelTagsController::class, "removeTag"]);

            /**
             * Files for Model
             */
            // Get a list of files attached to the specified model
            $group->get("/files/{id:[0-9]+}", [ModelFilesController::class, "getFiles"]);
            // Get a list of files of the given file-type attached to the specified model
            $group->get("/files/{id:[0-9]+}/{type}", [ModelFilesController::class, "getFilesWithType"]);
            // Update files for a model
            $group->post("/files/{id:[0-9]+}", [ModelFilesController::class, "updateFiles"]);

            // Download a zip file including all files of the specified file-type for the given model
            $group->get("/zip/{id:[0-9]+}/{type}", [ModelFilesController::class, "downloadZipFile"]);

            // Download main image
            $group->get("/file/main/{id:[0-9]+}", [ModelFilesController::class, "getMainImage"]);
            // Download a single file
            $group->get("/file/{fileId:[0-9]+}", [ModelFilesController::class, "getFile"]);
            // Save a file for a model
            $group->post("/file/{id:[0-9]+}", [ModelFilesController::class, "saveFile"]);
            // Delete the specified file assigned to a model
            $group->delete("/file/{fileId:[0-9]+}", [ModelFilesController::class, "deleteFile"]);
        });

        /**
         * Tags
         */
        // Get all tags
        $group->get("/tags/all", [ModelTagsController::class, "getAllTags"]);

        /**
         * Import
         */
        $group->group("/import", function (RouteCollectorProxy $group) {
            // Get enabled importers
            $group->get("/enabled", [ImportController::class, "getEnabledImporters"]);
            // Import
            $group->post("/{importer}", [ImportController::class, "import"]);
        });
    })->add(new VerifiedMiddleware())->add(new AuthMiddleware());
});


/**
 * Catch-all route to serve a 404 Not Found page if none of the routes match
 * NOTE: make sure this route is defined last
 */
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request) {
    throw new HttpNotFoundException($request);
});

$app->addErrorMiddleware(false, true, true);
// Add CORS middleware
$app->add(new CORSMiddleware());
$app->addBodyParsingMiddleware(); // Replaceable with json_decode($request->getBody()->getContents(),true)

$app->run();
