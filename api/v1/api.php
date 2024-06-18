<?php define( '__APP_START__', true );

/**
 * S3 v2 - API Bootstrap - 31 Aug 2023
 * 
 * This file is used by API endpoint controllers to bootstrap the App and core services.
 * e.g. api/v1/clients will include this file (api.php) first.
 * 
 * @id: MyHubApi
 * @name: My Currency Hub API
 * @author: Neels Moller <neels@currencyhub.co.za>
 * 
 * @version: 3.0 - FT - 16 May 2024
 *   - Set the auth user based on the auth token
 *   - Add a custom log file name based on the user's email
 *   - Add some comments
 */

require __DIR__ . '/../../.env-local';


$app = new stdClass();

$app->id = 'MyHubApi';
$app->ver = 'v1';
$app->today = date( 'Y-m-d' );
$app->name = 'MyHub API';

$app->pagesRef = 'pages';
$app->storageRef = 'storage';
$app->uploadsRef = 'uploads';
$app->partialsRef = '_partials';

$app->pagesDir = __ROOT_DIR__ . __DS__ . $app->pagesRef;
$app->storageDir = __ROOT_DIR__ . __DS__ . $app->storageRef;
$app->uploadsDir = __ROOT_DIR__ . __DS__ . $app->uploadsRef;

$app->modules = [ 'api' ];

$app->dbConn = [ 'dbhost' => __DB_HOST__, 'dbname' => __DB_NAME__,
    'username' => __DB_USER__, 'password' => __DB_PASS__ ];

$app->baseUri = '/' . __BASE_REF__ . ( __BASE_REF__ ? '/' : '' );

$app->cfg = require __DIR__ . '/../../config/api.php';


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
 * Security vectors
 */
$app->ipAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$app->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$app->logger->log( $app->ipAddr . ' - ' . $app->userAgent, 'info' );

$app->apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$app->logger->log( 'HTTP_X_API_KEY: ' . $app->apiKey, 'info' );

$app->httpAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$app->logger->log( 'HTTP_AUTHORIZATION: ' . $app->httpAuth, 'info' );

$app->bearerToken = $app->httpAuth ? str_replace( 'Bearer ', '', $app->httpAuth ) : null;
$app->logger->log( 'Bearer Token: ' . $app->bearerToken, 'info' );


/**
 * Validate security vectors
 */
if ( $app->bearerToken and defined( '__API_SECRET_KEY__' ) ) {

  // Use Security without starting a session. i.e. Last Arg == false
  // Security will decrypt the bearer token for us and we can get to it using getToken().
  $security = new F1\Security( __API_SECRET_KEY__, $app->bearerToken, false );


  $userData = [];
  $token = $security->getToken();

  // If token is a string, assign it to $userEmail.
  
  if ( is_string( $token ) ) {
    $userData['email'] = $token;
  } 
  else {
    $userData = $token['u'] ?? null;

    if ( ! $userData ) {
      $app->logger->log( 'ERROR: Bearer token format invalid!', 'error' );
      respond_with( 'Bad request', 400 );
    }

    if ( empty( $userData['idn'] ) and empty( $userData['uid'] ) and empty( $userData['id'] ) ) {
      $app->logger->log( 'ERROR: Bearer token format invalid!', 'error' );
      respond_with( 'Bad request', 400 );
    }
  }

  $userEmail = $userData['email'] ?? null;
  debug_log( $userData, 'USER DATA: ', 1, 'info' );
  debug_log( $userEmail, 'USER EMAIL: ', 1, 'info' );


  // TODO: Compare to whitelist of RPC clients. Also include the IP if fixed.
  // For now, just test for a single Google Sheet client and log a warning.
  if ( preg_match( '/id: ([^)]+)/', $app->userAgent, $matches ) ) {
    $agentId = $matches[1];
    debug_log( $agentId, 'GOOGLE SHEET Agent ID: ', 1, 'info' );
    if ( $agentId != __GOOGLE_TRADESHEET_VM_ID__ ) {
      $app->logger->log( 'WARNING: The requesting Agent ID does not match: ' . 
       __GOOGLE_TRADESHEET_VM_ID__, 'warning' );
    }
  } else {
    $app->logger->log( 'ERROR: GOOGLE SHEET: *None*', 'warning' );
  }

  // Auth User
  $app->user = $userData ? (object) $userData : new stdClass();
  $app->user->user_id = '_apicall_';

  if ( isset( $userEmail ) ) {
    use_database();
    $authUser = $app->db->table( 'users' )->where( 'email', $userEmail )->getFirst();
    if ( $authUser ) {
      $app->user->user_id = $authUser->user_id ?? '_apicall_';
    } else {
      $app->logger->log( 'ERROR: User not found for email: ' . $userEmail, 'error' );
      respond_with( 'User not found', 404 );
    }
    $apiLogsDir = $app->storageDir . __DS__ . 'logs' . __DS__ . 'api';
    $logFile = str_replace( '.', '', $userEmail ) . '_' . $app->today;
    if ( __DEBUG__ >= 2 ) $logFile .= '_' . date( 'H' );
    $logFile .= '.txt';
    $app->logger = new App\Services\AppLogger( $app, $apiLogsDir, $logFile );
  }

} else {
  $app->logger->log( 'ERROR: API Call Failed!', 'error' );
  debug_log( $_SERVER, 'ERROR. Bad Request! ', 3 );
  respond_with( 'Bad request', 400 );
}


/**
 * Run Endpoint Logic
 */

// Execute the code in the API controller that included this file...
