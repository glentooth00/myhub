<?php /* Accountant Module - Clients SPA - Client Trades Sub Controller */


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

debug_log(count($client->trades), 'Client Trades: ');


// Client calculated fields

$client->sda_remaining = $client->sda_mandate - $client->sda_used;
$client->fia_remaining = $client->fia_mandate - $client->fia_used;
$client->fia_unused = $client->fia_approved - $client->fia_used;
$client->fia_available = min($client->fia_unused, $client->fia_remaining);


// Trade Totals

$tradeTotals = new stdClass();
$tradeTotals->id = 0;
$tradeTotals->date = '';
$tradeTotals->trade_id = '';
$tradeTotals->sda_fia = '';
$tradeTotals->zar_sent = 0;
$tradeTotals->usd_bought = 0;
$tradeTotals->sda_used = 0;
$tradeTotals->fia_used = 0;
$tradeTotals->zar_profit = 0;
$tradeTotals->percent_return = 0;
$tradeTotals->amount_covered = 0;
$tradeTotals->sda_covered = 0;
$tradeTotals->fia_covered = 0;
$tradeTotals->sda_covered_usd = 0;
$tradeTotals->fia_covered_usd = 0;
$tradeTotals->allocated_pins = '';

foreach( $client->trades as $trade ) {
  $zarUsed = $trade->zar_sent;
  $tradeTotals->zar_sent += $trade->zar_sent;
  $tradeTotals->usd_bought += $trade->usd_bought;
  $tradeTotals->amount_covered += $trade->amount_covered;
  $coverAsPercentOfZarUsed = $trade->amount_covered / $zarUsed;
  $allocs = json_decode( $trade->allocated_pins?:'[]', true );
  $allocs_usd = toUsdAllocations( $allocs, $trade->usd_bought, $zarUsed );
  $trade->allocated_pins_usd = $allocs_usd ? json_encode( $allocs_usd ) : null;
  if ( $trade->sda_fia == 'SDA' ) {
    $tradeTotals->sda_covered += $trade->amount_covered;
    $tradeTotals->sda_covered_usd += $trade->usd_bought * $coverAsPercentOfZarUsed;
    $tradeTotals->sda_used += $trade->zar_sent;
  }
  if ( $trade->sda_fia == 'FIA' ) {
    $tradeTotals->fia_covered += $trade->amount_covered;
    $tradeTotals->fia_covered_usd += $trade->usd_bought * $coverAsPercentOfZarUsed;
    $tradeTotals->fia_used += $trade->zar_sent;
  }
  if ( $trade->sda_fia == 'SDA/FIA' ) {
    debug_log($allocs, 'SDA/FIA Alloc! ', 3);
    $sdaCovered = isset($allocs['_SDA_']) ? $allocs['_SDA_'] : 0;
    $sdaCoveredUsd = $sdaCovered ? $allocs_usd['_SDA_'] : 0;
    $tradeTotals->sda_covered += $sdaCovered;
    $tradeTotals->sda_used += $sdaCovered;
    $fiaCovered = $trade->amount_covered - $sdaCovered;
    $fiaCoveredUsd = $trade->usd_bought * $coverAsPercentOfZarUsed - $sdaCoveredUsd;
    $tradeTotals->fia_covered += $fiaCovered;
    $tradeTotals->fia_covered_usd += $fiaCoveredUsd;
    $tradeTotals->fia_used += $fiaCovered;
  }
}