<?php /* helpers.php */

function debug_dump( $var = null, $pretext = '', $minLevel = 1 )
{
  if ( ! __DEBUG__ or __DEBUG__ < $minLevel ) return;
  echo '<pre>', $pretext, print_r( $var, true ), '</pre>';
}


function debug_log( $var = null, $pretext = '', $minLevel = 1, $type = 'debug' )
{
  global $app;
  if ( ! __DEBUG__ or __DEBUG__ < $minLevel ) return;
  if ( $var === PHP_EOL ) $app->logger->nl();
  else $app->logger->log( $pretext . print_r( $var, true ), $type );
}


function debug_clean_logs( $logsDir = null )
{
  global $app;
  $fs = new F1\FileSystem();
  $logsDir = $logsDir ?: $app->storageDir . __DS__ . 'logs';
  $fs->emptyDir( $logsDir, 'recursive', 'keepSubDirs' );
  $app->logger->log( 'debug_clean_logs(), Logs cleared!', 'debug' );
  $app->logger->nl();
}


function log_request_start( $newLines = 0 )
{
  global $app;
  if ( ! __DEBUG__ ) return;
  $sessionID = session_id();
  $ajaxType = $app->request->isAjax ? 'AJAX ' : '';
  $reqMethod = $ajaxType . ( $app->request->isPost ? 'POST' : 'GET' );
  $reqType = ( ! $ajaxType or $app->request->type === 'page' ) ? 'PAGE ' :'';
  $newLines = $newLines ?: ( $app->logger->isNewLog ? 0 : 1 );
  if ( $newLines ) $app->logger->nl( $newLines );
  $app->logger->log( '*** ' . $ajaxType . $reqType . 'REQUEST *** ' ); 
  $app->logger->log( $reqMethod . ' ' . $_SERVER['REQUEST_URI'], 'request' );
  $app->logger->log( 'File: ' . $app->request->controllerFile, 'request' );  
  if ($sessionID) $app->logger->log( 'Session ID: ' . $sessionID, 'request' );  
}


function use_config( $configDir = null )
{
  global $app;
  if ( isset( $app->config ) ) return $app->config;
  $app->config = new F1\Config( $configDir ?: __ROOT_DIR__ . __DS__ . 'config' );
  return $app->config;
}


function use_database( $dbConn = null )
{
  global $app;
  if ( isset( $app->db ) ) return $app->db;
  $app->db = new F1\Database( $dbConn?:$app->dbConn );
  return $app->db;
}


function use_security()
{
  global $app;
  if ( isset( $app->security ) ) return $app->security;
  $app->security = new F1\Security( __SECRET_KEY__ );
  if ( __DEBUG__ ) {
    $uid = $app->security->getUserId();
    if ( $uid ) {
      // Replace logger with one that includes the user ID.
      $app->logger = new App\Services\AppLogger( $app );
      if ( $app->logger->isNewLog ) {
        $username = $app->security->getUsername();
        $app->logger->log( '*** USER LOG ***' );
        $app->logger->log( "U$uid, $username" );
      }
      log_request_start( 2 );
    } else {     
      $app->logger->log( 'No current user found. Logging in?', 'App Security', 2 );
    }
  }
  return $app->security;
}


function use_google()
{
  global $app;
  if ( isset( $app->google ) ) return $app->google;
  $app->google = new App\Services\GoogleAPI( new F1\HttpClient(),
    __GOOGLE_PRIVATE_KEY__, __GOOGLE_SERVICE_EMAIL__ );
  return $app->google;
}


/**
 * @param string|array $allowed - Allowed roles/groups. e.g. 'accountant' or 'admin,super'
 */
function allow( $allowed )
{
  global $app;
  $security = use_security();
  debug_log( json_encode( $allowed ), 'Allowed Role/Group: ', 3, 'App Security' );
  if ( ! $security->isLoggedIn() ) redirect( 'user/login' );
  if ( __MAINTENANCE_MODE__ ) $allowed = 'super';
  $roles = [];
  $super = [ 'super-admin', 'sysadmin' ];
  $admin = [ 'admin', 'super-admin', 'sysadmin' ];
  if ( is_string( $allowed ) ) { $allowed = explode( ',', $allowed ); }
  $allowed = $allowed ? array_map( 'trim', $allowed ) : [];
  foreach ( $allowed as $role ) {
    if ($role === 'super') $roles = [...$roles, ...$super];
    else if ($role === 'admin') $roles = [...$roles, ...$admin];
    else $roles[] = $role;
  }
  debug_log( json_encode( $roles ), 'Allowed Roles Expanded: ', 4, 'App Security' );
  if ( $roles[0] !== 'logged-in') $security->denyIfRoleNot( $roles, 'Access denied' );
  $db = use_database();
  $app->user = $db->getFirst( 'users', $security->getUserId() );
  $app->user->role = $security->getUserRole();
  $app->user->password = '***';
  $app->logger->nl();
}


function redirect( $url = '', $code = 302, $extraHeaders = [] )
{
  global $app;
  $req = $app->request;
  if ( $url === 'user/login' and empty( $app->user ) ) $url = 'user/login/expired';
  if ( $req->isAjax and $req->type !== 'page' ) {
    if ($url === 'user/login/expired') json_response( [ 'message' => 'Your session expired.' .
      '<a href="user/login">Please login again.</a>' ] );
    else json_response( [ 'redirect' => $url ], $code, $extraHeaders );
  }
  if ( strpos( $url, '://' ) === false ) $url = $app->baseUri . ltrim( $url, '/' );
  foreach ( $extraHeaders as $header ) header( $header );
  http_response_code( $code );
  header( 'Location: ' . $url );
  exit;
}


function page_link( $fileRef = '' )
{
  global $app;
  return $app->pagesRef . '/' . $app->request->path . '/' . $fileRef;
}


function respond_with( $textHtml, $code = 200, $extraHeaders = [] )
{
  foreach ( $extraHeaders as $header ) header( $header );
  http_response_code( $code );
  echo $textHtml;
  exit;
}


function json_response( $data = [], $code = 200, $extraHeaders = [], $return = false )
{
  header( 'Content-Type: application/json' );
  foreach ( $extraHeaders as $header ) header( $header );
  http_response_code( $code );
  if ( $return ) return json_encode( $data );
  echo json_encode( $data );
  exit;
}


function download_response( $file, $saveAs = null, $code = 200, $extraHeaders = [] )
{
  header( 'Content-Description: File Transfer' );
  header( 'Content-Type: application/octet-stream' );
  header( 'Content-Disposition: attachment; filename="' . ( $saveAs ?: basename( $file ) ) . '"' );
  header( 'Expires: 0' );
  header( 'Cache-Control: must-revalidate' );
  header( 'Pragma: public' );
  header( 'Content-Length: ' . filesize( $file ) );
  foreach ( $extraHeaders as $header ) header( $header );
  http_response_code( $code );
  readfile( $file );
  exit;
}


function run_google_script( $scriptName, $payload = [], $userEmail = null )
{
  $config = use_config();
  $google = use_google();
  $cmd = $config->get( 'google', $scriptName );
  $scriptsVersionId = __GOOGLE_SCRIPTS_VERSION_ID__;
  $rpcResp = $google->callAppsScript( $scriptsVersionId, $cmd, $payload, $userEmail );
  $rpcResult = $google->validateAppScriptCallResponse( $rpcResp );
  debug_log( $rpcResult, "Google RPC ($scriptName) Result: ", 2 );
  return $rpcResult;
}


function process_upload( $fieldName, $currentValue, $postedValue, 
  $saveDir, $saveAsBaseName, $deleteOld = false )
{
  debug_log( compact( 'fieldName', 'currentValue', 'postedValue', 'saveDir', 
    'saveAsBaseName', 'deleteOld' ), 'process_upload(), params: ', 2 );  
  $fileInfo = $_FILES[ $fieldName ] ?? [];
  $fileStatus = $fileInfo['error'] ?? null;
  if ( $fileStatus > 0 and $fileStatus !== UPLOAD_ERR_NO_FILE ) // Bad status...
    throw new Exception( "Failed to upload $fieldName. Error code: $fileStatus" );
  if ( $postedValue != $currentValue ) {
    $existingFile = $saveDir . __DS__ . $currentValue;
    if ( $currentValue and file_exists( $existingFile ) ) {
      if ( $deleteOld ) unlink( $existingFile );
      else rename( $existingFile, $existingFile . '_' . time() . '.bak' );
    }
    if ( $postedValue ) {
      if ( $fileInfo['size'] > 2097152 )
        throw new Exception( 'File size > 2MB: ' . $fileInfo['name'] );
      if ( ! is_dir( $saveDir ) ) mkdir( $saveDir, 0777, true );
      $ext = strtolower( pathinfo( $fileInfo['name'], PATHINFO_EXTENSION ) );
      $saveAsName = $saveAsBaseName . '.' . $ext;
      $saveAs = $saveDir . __DS__ . $saveAsName;
      if ( ! move_uploaded_file( $fileInfo['tmp_name'], $saveAs ) )
        throw new Exception( 'Failed to upload file: ' . $fileInfo['name'] );
      debug_log( $saveAs, 'Save File as: ', 2 );
      $_FILES[ $fieldName ]['save_as'] = $saveAs;
      $fileInfo['name'] = $saveAsName;
      $postedValue = $saveAsName;
    }
  }
  return $postedValue ?: null;
}


function abort_uploads( $caller = 'exception' )
{
  $files = $_FILES ?: [];
  debug_log( $caller, 'abort_uploads(), caller: ', 2 );
  debug_log( $files, 'abort_uploads(), files: ', 2 );
  foreach ( $files as $file ) {
    $tmpName = $file['tmp_name'] ?? null;
    $saveAs = $file['save_as'] ?? null;
    if ( $tmpName and file_exists( $tmpName ) ) unlink( $tmpName );
    if ( $saveAs and file_exists( $saveAs ) ) unlink( $saveAs );
  }
}


function escape( $val, $convert = false )
{
  if ( $val === null ) return;
  if ( $convert ) $val = mb_convert_encoding( $val, 'UTF-8', 'UTF-8,ISO-8859-1' );
  return htmlspecialchars( $val, ENT_QUOTES, 'UTF-8' );
}


/**
 * Used in TradeModel etc to clean up currency values.
 * e.g. $trade->zar_sent = decimal($values[5]) ?? null;
 */
function decimal( $decimalString, $emptyValue = '' )
{
  $r = preg_replace( '/[^0-9.]/', '', $decimalString );
  return $r === '' ? $emptyValue : $r;
}


function currency( $number, $symbol = 'R', $thousandSeparator = ' ', $decimalPlaces = 0 )
{
  if ( $number === null ) return '';
  $formattedNumber = number_format( $number, $decimalPlaces, '.', $thousandSeparator );
  $formattedCurrency = $symbol . $formattedNumber;
  return $formattedCurrency;
}


function full_url( $ref )
{
  global $app;
  return __WEB_HOST__ . $app->baseUri . ltrim( $ref, '/' );
}


function full_name( $user )
{
  if ( ! $user ) return '';
  $name = trim( $user->first_name . ' ' . $user->last_name );
  return $name;
}


function replace_start( $find, $repl, $str )
{
  return strpos( $str, $find ) === 0 ? substr_replace( $str, $repl, 0, strlen( $find ) ) : $str;
}


function json_stringify( $assocArray )
{
  $jsonParts = [];
  foreach ( $assocArray as $key => $value ) {
    $value = round($value, 3);
    $jsonParts[] = '"' . $key . '":' . $value;
  }
  return '{' . implode( ',', $jsonParts ) . '}';
}


function json_parse( $str )
{
  $pairs = explode( ',', trim( $str, '{}' ) );
  $assocArray = array();
  foreach ( $pairs as $pair ) {
    list( $key, $value ) = explode( ':', $pair );
    $key = trim( $key, '"' );
    $value = trim( $value, '"' );
    $assocArray[$key] = $value + 0;
  }
  return $assocArray;
}
