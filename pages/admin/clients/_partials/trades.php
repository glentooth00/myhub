<?php /* Admin Module - Clients SPA - Client Trades Sub Controller */


// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? null;

if ( ! is_numeric( $id ) ) respond_with( 'Bad request', 400 );




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 3 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 3 );


    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    $app->db->safeRollBack();
    json_response( [ 'success' => false, 'message' => $error ] );
  }

}




// ---------
// -- GET --
// ---------

function toUsdAllocations( $zarAllocations, $usdBought, $zarUsed )
{
  $usdAllocations = [];
  foreach( $zarAllocations as $pin => $coverAmount ) {
    $coverAsPercentOfZarUsed = $coverAmount / $zarUsed;
    $coverUSD = $usdBought * $coverAsPercentOfZarUsed;
    $usdAllocations[$pin] = number_format( $coverUSD, 3, '.', '' );
  }
  return $usdAllocations;
}


$client = $app->db->getFirst( 'clients', $id );

if ( ! $client ) respond_with( "Error: Invalid client id=$id.", 500 );


$year = date( 'Y' );

$client->trades = $app->db->table( 'trades' )
  ->where( 'deleted_at', 'IS', null )
  ->where( 'client_id', '=', $client->client_id )
  ->orderBy( '`date`' )->getAll();

debug_log(count($client->trades), 'Client All Trades: ');

foreach( $client->trades as $trade ) {
  $zarUsed = $trade->zar_sent;
  $allocs = json_decode( $trade->allocated_pins?:'[]', true );
  $allocs_usd = toUsdAllocations( $allocs, $trade->usd_bought, $zarUsed );
  $trade->allocated_pins_usd = $allocs_usd ? json_encode( $allocs_usd ) : null;
}


$super = ( $app->user->role == 'sysadmin' or $app->user->role == 'super-admin' );