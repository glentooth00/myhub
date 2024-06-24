<?php define( '__APP_START__', true );

/**
 * F1 - Front Controller - 01 June 2023
 * 
 * @id: MyHub
 * @name: My Currency Hub
 * @author: Neels Moller <neels@currencyhub.co.za>
 * @version: 1.0 - RC262.N1 20 Jun 2024
 *
 */

require '.env-local';

$app = new stdClass();

$app->id = 'MyHub';
$app->ver = 'v1.RC262.N1';
$app->today = date( 'Y-m-d' );
$app->name = 'My Currency Hub';
$app->theme = 'default-theme';

$app->pagesRef = 'pages';
$app->storageRef = 'storage';
$app->partialsRef = '_partials';
$app->uploadsRef = __UPLOADS_REF__;

$app->baseUri = '/' . __BASE_REF__ . ( __BASE_REF__ ? '/' : '' );

$app->pagesDir = __ROOT_DIR__ . __DS__ . $app->pagesRef;
$app->storageDir = __ROOT_DIR__ . __DS__ . $app->storageRef;
$app->templatesDir = $app->pagesDir . __DS__ . '_templates' . __DS__ . $app->theme;
$app->uploadsDir = __UPLOADS_DIR__;

$app->bootLog = [ 'Booting App: ' . print_r( $app->ver, true ) ];

$app->modules = [ 'user', 'admin', 'accountant', 'client', 'website' ];

$app->dbConn = [ 'dbhost' => __DB_HOST__, 'dbname' => __DB_NAME__,
    'username' => __DB_USER__, 'password' => __DB_PASS__ ];


/**
 * Extend PHP
 */
require __INCLUDES_DIR__ . __DS__ . 'exceptions.php';
require __INCLUDES_DIR__ . __DS__ . 'autoload.php';
require __INCLUDES_DIR__ . __DS__ . 'helpers.php';


/**
 * Minimum services required
 */
$app->logger = new App\Services\AppLogger( $app );
$app->request = new App\Services\AppRequest( $app );


log_request_start();


/**
 * Run
 */
require $app->request->getControllerFile();