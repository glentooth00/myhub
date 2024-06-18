<?php /* Accountant Module - Clients SPA - Client TCCs Sub Controller */


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


$client = $app->db->getFirst( 'clients', $id );

if ( ! $client ) respond_with( "Error: Invalid client id=$id.", 500 );


$year = date( 'Y' );

$client->tccs = $app->db->table( 'tccs' )
  ->where( 'deleted_at', 'IS', null )
  ->where( 'client_id', '=', $client->client_id )
  ->orderBy( '`date`' )->getAll();

debug_log(count($client->tccs), 'Client TCCs: ');


// Client calculated fields

$client->sda_remaining = $client->sda_mandate - $client->sda_used;
$client->fia_remaining = $client->fia_mandate - $client->fia_used;
$client->fia_unused = $client->fia_approved - $client->fia_used;
$client->fia_available = min($client->fia_unused, $client->fia_remaining);


// TCC Totals

$tccTotals = new stdClass();
$tccTotals->pin_values = 0;
$tccTotals->pending = 0;
$tccTotals->approved = 0;
$tccTotals->rollover = 0;
$tccTotals->reserved = 0;
$tccTotals->remaining = 0;
$tccTotals->available = 0;
$tccTotals->allocated = 0;

foreach( $client->tccs as $tcc ) {
  $tccTotals->pin_values += $tcc->amount_cleared;
  $tccTotals->pending += $tcc->status == 'Pending' ? $tcc->amount_cleared_net : 0;
  $tccTotals->approved += $tcc->status == 'Approved' ? $tcc->amount_cleared_net : $tcc->rollover;
  $tccTotals->rollover += $tcc->rollover;
  $tccTotals->reserved += $tcc->amount_reserved;
  $tccTotals->remaining += $tcc->amount_remaining;
  $tccTotals->available += $tcc->amount_available;
  $tccTotals->allocated += $tcc->amount_used;
}