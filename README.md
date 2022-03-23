# 3D Model Manager (Backend)
The 3D Model Manager is a webservice to collect and manage your 3D models.

All info is collected in the frontend repository, link below.

## Structure of the software
The backend (this repository) is based on Laravel 8 (PHP) and a MySQL/MariaDB database. The frontend of laravel is not used and will (hopefully) be discarded, only the API server is used. Composer is required.

The frontend ([another repository](https://github.com/Micky261/3d-model-manager-frontend)) is based on Angular 10 with Typescript and Bootstrap. Yarn is required.

The frontend and backend are completely separated and shall work independently, which means that you should be able to set up a frontend yourself and use any available 3D Model Manager API available.

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

### Start server
You can use the built-in php server to start the application:
```shell
php -S localhost:8000 -t public/
```
