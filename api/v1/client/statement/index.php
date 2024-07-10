<?php /* API Endpoint Controller: "api/v1/client/statement" */

require __DIR__ . '/../../api.php';


// Remember, we log the requester IP and Agent in "api.php",
// so we do not have to do it here.
debug_log( 'API endpoint: "api/v1/client/statement" says hi!  ' .
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

// e.g. api/v1/client/statement?cuid=neelsdev&year=[yyyy]&timestamp=1234567890
// e.g. api/v1/client/statement?cidn=9401245149086&year=[yyyy]&timestamp=1234567890

// if ( ! $app->request->isRPC ) respond_with( 'Bad request', 400 );
// if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

debug_log( $_GET, 'API GET Request. Params = ', 3 );

$clientUid = $_GET['cuid'] ?? $app->user->uid ?? null;
$clientIdNo = $_GET['cidn'] ?? $app->user->idn ?? null;
$year = $_GET['year'] ?? date( 'Y' );
$asPDF = $_GET['pdf'] ?? true;


debug_log( $clientUid, 'Client UID = ', 2 );
debug_log( $clientIdNo, 'Client ID No = ', 2 );
debug_log( $year, 'Year = ', 2 );
debug_log( $asPDF, 'As PDF = ', 2 );


if ( ! $clientUid and !$clientIdNo ) respond_with( 'Bad request', 400 );


try {  

  use_database();

  $client = $app->db->table( 'clients' )
    ->where( 'client_id', '=', $clientUid )
    ->orWhere( 'id_number', '=', $clientIdNo )
    ->getFirst();

  if ( ! $client ) {
    $app->logger->log( 'ERROR: Client not found!', 'error' );
    respond_with( 'Client not found', 404 );
  }

  debug_log( $client, 'Client = ', 4 );

  $statement = new App\Models\ClientStatement( $app );
  $statement->generate( $client, [ 'year' => $year ] );


  if ( ! $asPDF ) {
    
    $response = [
      'year' => $year,
      'date' => $statement->getData( 'date' ),
      'date_range' => $statement->getData( 'dateRange' ),
      'trading_capital' => $client->trading_capital ?? 0,
      'ait_avail' => $statement->getData( 'fiaAvail' ),
      'ait_remain' => $statement->getData( 'fiaMandateRemaining' ),
      'sda_avail' => $statement->getData( 'sdaMandateRemaining' ),
      'total_net_profit' => $statement->getData( 'totalNetProfit' ),
      'referLink' => 'https://www.currencyhub.co.za/customer-dashboard/ch-referral/',
      'client' => $client,
      'lines' => $statement->getData( 'lines' ),
    ];

    json_response( $response );
    
    exit;

  }


  $clientNameSlug = urlencode( str_replace( ' ', '_', $client->name ) );
  $filename = "CH_Statement_{$clientNameSlug}_{$year}.pdf";
  $saveDir = $app->uploadsDir . __DS__ . $client->name . '_' . $client->id . __DS__ . 'statements';

  if ( ! is_dir( $saveDir ) ) mkdir( $saveDir, 0777, true );

  $file = $saveDir . __DS__ . $filename;


  if ( file_exists( $file ) and filemtime( $file ) >= strtotime( '-30 minutes' ) )
  {

    // Serve the cached file if it exists and is not older than 30 minutes.
    download_response( $file );

  }


  /* Generate a new Statement PDF file and serve it. */

  include $app->pagesDir . __DS__ . '_templates' . __DS__ . 'default-theme' . 
    __DS__ . 'client' . __DS__ . 'stmtPDF.php';


  global $pdf; // $pdf is defined in the included file.
  $pdf->Output( $file, 'F' );

  download_response( $file );

}

catch ( Error $e ) {
  $app->logger->log( $e->getMessage(), 'error' );
  echo 'Oops, something went wrong!';
}

catch ( Exception $e ) {
  $app->logger->log( $e->getMessage(), 'exception' );
  echo 'Oops, something went wrong!';
}