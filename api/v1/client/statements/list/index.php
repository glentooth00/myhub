<?php /* API Endpoint Controller: "api/v1/client/statements/list" */

require __DIR__ . '/../../../api.php';


// Remember, we log the requester IP and Agent in "api.php",
// so we do not have to do it here.
debug_log( 'API endpoint: "api/v1/client/statements/list" says hi!  ' .
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

// e.g. api/v1/client/statements/list?cuid=john123&timestamp=1234567890
// e.g. api/v1/client/statements/list?cidn=9401245149086&timestamp=1234567890

// if ( ! $app->request->isRPC ) respond_with( 'Bad request', 400 );

debug_log( $_GET, 'API GET Request. Params = ', 3 );

$clientUid = $_GET['cuid'] ?? $app->user->uid ?? null;
$clientIdNo = $_GET['cidn'] ?? $app->user->idn ?? null;


debug_log( $clientUid, 'Client UID = ', 2 );
debug_log( $clientIdNo, 'Client ID No = ', 2 );


if ( ! $clientUid and !$clientIdNo ) respond_with( 'Bad request', 400 );


try {  

  use_database();

  $infoSet = 'id, client_id as uid, id_number, personal_email, ' .
    'sda_mandate, fia_mandate, trading_capital';

  $client = $app->db->table( 'clients' )->select( $infoSet )
    ->where( 'client_id', '=', $clientUid )
    ->orWhere( 'id_number', '=', $clientIdNo )
    ->getFirst();  

  if ( ! $client ) {
    $app->logger->log( 'ERROR: Client not found!', 'error' );
    respond_with( 'Client not found', 404 );
  }

  debug_log( $client, 'Client = ', 4 );

  $currentYear = date( 'Y' );

  $currentYearInfo = new stdClass();
  $currentYearInfo->year = $currentYear;
  $currentYearInfo->sda_mandate = $client->sda_mandate;
  $currentYearInfo->fia_mandate = $client->fia_mandate;
  $currentYearInfo->trading_capital = $client->trading_capital;

  $infoSet = 'year, sda_mandate, fia_mandate, trading_capital';
  $annualInfo = $app->db->table( 'clients_annual_info' )
    ->select( $infoSet )
    ->where( 'client_id', $client->id )
    ->orderBy( 'year DESC' )
    ->getAll();

  debug_log( $annualInfo, 'Client Annual Info = ', 4 );

  array_unshift( $annualInfo, $currentYearInfo );

  json_response( $annualInfo );

}

catch ( Error $e ) {
  $app->logger->log( $e->getMessage(), 'error' );
  echo 'Oops, something went wrong!';
}

catch ( Exception $e ) {
  $app->logger->log( $e->getMessage(), 'exception' );
  echo 'Oops, something went wrong!';
}