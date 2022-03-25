# 3D Model Manager (Backend)
The 3D Model Manager is a webservice to collect and manage your 3D models.

All information can be found in the frontend repository → [3D Model Manager Frontend](https://github.com/Micky261/3d-model-manager-frontend).

## Structure of the software
The backend (this repository) is based on the Slim 4 framework (PHP) with a database (known to be working: MariaDB/MySQL). Composer is required.

The frontend ([3D Model Manager Frontend](https://github.com/Micky261/3d-model-manager-frontend)) is based on the Angular framework with Bootstrap. Yarn is required.

The frontend and backend are completely separated and shall work independently, which means that you should be able to set up a frontend yourself and use any available 3D Model Manager API available. (CORS and CSRF-protection not fully developed yet!)

## Importer
### Thingiverse
Register an app on [Thingiverse](https://www.thingiverse.com/apps/create). After the creation you will get an App Token, which needs to be put into the `app-config.json` file.

Please note that this app token is for your personal use (testing/development). Logging in with the API is not possible yet.<br />
It works, because only read-only API endpoints are used.

### MyMiniFactory
Register an app on [MyMiniFactory](https://www.myminifactory.com/settings/developer/application). After the creation you can generate an API Key, which needs to be put into the `app-config.json` file.

Logging in with the API is not possible yet.<br />
It works, because only read-only API endpoints are used.

### Sketchfab
#### Variant 1: Use only freely available endpoints
Leave the `api-token` empty, only data freely available on the API will be used to import.

This unfortunately excludes downloads of 3D models.

#### Variant 2: Use your personal access token
In the [password settings](https://sketchfab.com/settings/password) get your API token and put it into the `app-config.json` file. 

This token is for your personal use only!

#### Variant 3: Let your users login
Logging in with the API is not possible yet.

## Developer setup
### Dependencies
Check out the repository and in the root directory install all dependencies with
```shell
composer install
```

### Configuration
In the root directory copy the file `app-config.template.json` and name the new file `app-config.json`.

Enter your database information as you do with PDO (based on PDO).<br />
For local development you probably want to enable CORS too.<br />
The importer and mailer settings are currently unused.

### Fill database
Execute all scripts in the `db_scripts` folder in the database you configured.

### Register a user
The registration is not yet implemented, therefore you need to add a user manually.

In the `user` table create a new row. The email is needed for login, set an `email_verified_at` timestamp - null will prevent login.<br />
Add a password based on php's standard password function.
Or use the following string for the password "Adm1n":
```
$2y$10$E30Orp7QHg.ogp5FxA7pz.X3wxszgF.sSmDvLl45yORyLgT9TQbY.
```

### Start server
You can use the built-in php server to start the application:
```shell
php -S localhost:8000 -t public/
```

