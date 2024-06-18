<?php /* API Endpoint Controller: "api/v1/client/info" */

require __DIR__ . '/../../api.php';


// Remember, we log the requester IP and Agent in "api.php",
// so we do not have to do it here.
debug_log( 'API endpoint: "api/v1/client/info" says hi!  ' .
  'Request type = ' . $_SERVER['REQUEST_METHOD'], '', 2 );




// ---------
// -- CLI --
// ---------

if ( $app->request->cli ) respond_with( 'Bad request', 400 );




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) respond_with( 'Bad request', 400 );




// --------------------
// -- Verify API Key --
// --------------------

$allowedKeys = $app->cfg['apikeys']['statements'] ?? [];
if ( ! in_array( $app->apiKey, $allowedKeys ) ) {
  $app->logger->log( 'ERROR: Unauthorized API key: ' . $app->apiKey, 'error' );
  respond_with( 'Bad request', 400 );
}



// ---------
// -- GET --
// ---------

// e.g. api/v1/client/info?cuid=john123&timestamp=1234567890
// e.g. api/v1/client/info?cidn=9401245149086&timestamp=1234567890

// if ( ! $app->request->isRPC ) respond_with( 'Bad request', 400 );

debug_log( $_GET, 'API GET Request. Params = ', 3 );

$clientUid = $_GET['cuid'] ?? $app->user->uid ?? null;
$clientIdNo = $_GET['cidn'] ?? $app->user->idn ?? null;


debug_log( $clientUid, 'Client UID = ', 2 );
debug_log( $clientIdNo, 'Client ID No = ', 2 );


if ( ! $clientUid and !$clientIdNo ) respond_with( 'Bad request', 400 );


try {  

  use_database();

  $infoSet = 'id, client_id as uid, id_number, name, first_name, last_name, ' .
    'personal_email as email, status, sda_mandate, fia_mandate, trading_capital, created_at, deleted_at';

  $clientInfo = $app->db->table( 'clients' )
    ->select( $infoSet )
    ->where( 'client_id', '=', $clientUid )
    ->orWhere( 'id_number', '=', $clientIdNo )
    ->getFirst();

  if ( ! $clientInfo ) {
    $app->logger->log( 'ERROR: Client not found!', 'error' );
    respond_with( 'Client not found', 404 );
  }

  debug_log( $clientInfo, 'Client Info = ', 4 );

  json_response( $clientInfo );  

}

catch ( Error $e ) {
  $app->logger->log( $e->getMessage(), 'error' );
  echo 'Oops, something went wrong!';
}

catch ( Exception $e ) {
  $app->logger->log( $e->getMessage(), 'exception' );
  echo 'Oops, something went wrong!';
}