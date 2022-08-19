# 3D Model Manager (Backend)
The 3D Model Manager is a webservice to collect and manage your 3D models.

All information can be found in the frontend repository → [3D Model Manager Frontend](https://github.com/Micky261/3d-model-manager-frontend).

## Structure of the software
The backend (this repository) is based on the Slim 4 framework (PHP) with a database (known to be working: MariaDB/MySQL). Composer is required.

The frontend ([3D Model Manager Frontend](https://github.com/Micky261/3d-model-manager-frontend)) is based on the Angular framework with Bootstrap. Yarn is required.

The frontend and backend are completely separated and shall work independently, which means that you should be able to set up a frontend yourself and use any available 3D Model Manager API available. (CORS and CSRF-protection not fully developed yet!)

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
The mailer settings are currently unused. The settings for importer are described below.

### Storage
3D Model Manager supports multiple storages with several storage types. Currently supported are: local (on-disk) storage and storage on a WebDAV server (developed for and with Nextcloud, but it sshould work with almost all WebDAV servers).<br />
Each storage device can be limited in size/capacity.<br />
The default storage with name "Default" should be a local on-disk storage.

Examples:

```json
{
    // ...
    "storage": [
        {
            // Name of the storage referenced within the software
            "name": "Default",
            // Relative path to the storage location (as seen from public/ folder!)
            "url": "../upload/",
            // Capacity of the storage (either numerical in bytes or numerical with a magnitude (B=Byte, K=Kilobyte, M=Megabyte, G=Gigabyte, T=Terabyte)
            "capacity": "50G",
            // "local" for local on-disk storage
            "type": "local"
        },
        {
            // Name of the storage referenced within the software
            "name": "Default",
            // URL with path were to store the data. The URL must end with a slash "/".
            "url": "https://nc.example.com/remote.php/dav/files/<username>/<path to subfolder>/",
            // Capacity of the storage (either numerical in bytes or numerical with a magnitude (B=Byte, K=Kilobyte, M=Megabyte, G=Gigabyte, T=Terabyte)
            "capacity": "50G",
            // "webdav" for WebDAV storages
            "type": "webdav",
            // Username of the user which is used to access the WebDAV server. It's strongly adviced to create a new user for this software.
            "username": "username",
            // Password of the user which is used to access the WebDAV server. It's strongly adivced to use an app password and not the regular account password!
            "password": "app-password"
        }
    ]
}
```

All names must be unique and should contain alphanumeric characters with no spaces.

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

### Printables
[Printables] has a public API, which is not documented. The current implementation can break anytime.

The API can be used to get metadata and files without configuration and user credentials. The importer is enabled by default.
