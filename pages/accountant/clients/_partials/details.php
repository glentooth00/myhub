<?php /* Accountant Module - Clients SPA - Client Details Sub Controller */

global $app;

use App\Models\Client as ClientModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\ClientS2Response as ClientS2ResponseModel;
use App\Models\TradesSummary;
use App\Models\TccsSummary;


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


    /** ACTION 1 **/

    if ( $action === 'deleteClient' ) {

      json_response( [ 'success' => false, 'message' => 'Action not allowed.' ] );

    } // deleteClient



    /** ACTION 2 **/

    if ( $action === 'unSoftDeleteClient' ) {

      json_response( [ 'success' => false, 'message' => 'Action not allowed.' ] );

    } // unSoftDeleteTcc



    /** ACTION 3 **/

    if ( $action === 'updateAndSync' ) {

      $year = date( 'Y' );

      $noSync = $_POST['noSync'] ?? false;

      $client = $app->db->getFirst( 'clients', $id );
      if ( ! $client ) respond_with( "Client id=$id not found.", 404 );
      debug_log( $client, 'Client to update (before s2 rpc): ', 2 );


      $opts = [];
      $opts['redoAllocations'] = $_POST['redoAllocations'] ?? null;
      $opts['setRollovers'] = $_POST['setRollovers'] ?? null;
      $opts['year'] = $_POST['year'] ?? null;

      if ( $app->user->role != 'sysadmin' )
       if ( $opts['redoAllocations'] || $opts['setRollovers'] || $opts['year'] )
        json_response( [ 'success' => false, 'message' => 'Action not allowed.' ] );


      $app->db->pdo->beginTransaction();

      $clientStateModel = new ClientStateModel( $app );
      $updateResult = $clientStateModel->updateStateFor( $client, $opts );
      debug_log( $updateResult, 'Client Update State Result: ', 3 ); 


      if ( $noSync ) {
        $app->db->pdo->commit();
        json_response( ['success' => true, 'message' => 'Client data updated successfully.'] );
      }


      // ---------------
      // Sync Remote: S2
      // ---------------

      $responseModel = new ClientS2ResponseModel( $app );
      $payload = $responseModel->generate( $updateResult );

      $result = run_google_script( 'updateClient', $payload, 'neels@currencyhub.co.za' );

      $links = $result['links'] ?? null;

      if ( $links ) {
        $linkData = [];
        if ( isset( $links['smtUrl'] ) ) $linkData['statement_file'] = $links['smtUrl'];
        if ( isset( $links['pdfUrl'] ) ) $linkData['statement_pdf'] = $links['pdfUrl'];
        if ( $linkData ) {
          $linkData['id'] = $id;
          debug_log( $linkData, 'Update client link data: ', 3 );
          $app->db->table( 'clients' )->update( $linkData );
        }
      } else {
        debug_log( $result, 'WARNING: No "Statement Links" in Google API "updateClient" Result.', 2 );
      }


      $app->db->pdo->commit();
    
      json_response( ['success' => true, 'message' => 'Client data synced successfully.'] );

    } // updateAndSync



    /** ACTION 4 **/

    if ( $action === 'sendStatementLink' ) {

      json_response( [ 'success' => false, 'message' => 'Action not allowed.' ] );

    } // sendStatementLink



    /** DEFAULT ACTION **/

    throw new Exception( 'Invalid or missing request action.' );

  } // try

  catch ( Exception $ex ) {
    $app->db->safeRollBack();
    $message = $ex->getMessage();
    if ( __DEBUG__ > 2 ) {
      $file = $ex->getFile();
      $line = $ex->getLine();
      $message .= "<br>---<br>Error on line: $line of $file";
    }
    $app->logger->log( $message, 'error' );
    json_response( [ 'success' => false, 'message' => $message ] );
  }  

} // POST



// ---------
// -- GET --
// ---------

function fullName( $client )
{
  if ( $client->first_name ) {
    $fullName = $client->first_name;
    if ( $client->middle_name ) $fullName .= ' ' . $client->middle_name;
    if ( $client->last_name ) $fullName .= ' ' . $client->last_name;
  }
  else $fullName = $client->name;

  return $fullName;
}

function generatePDFLink( $clientUid, $year = 'current' )
{
  $url = 'client/statement?uid=' . $clientUid;
  if ( $year == 'last' ) $url .= '&year=' . ( date( 'Y' ) - 1 );
  return $url;
}

function clientDocsRef( $fieldName )
{
  global $app, $client;
  return $app->uploadsRef . '/' . $client->name . '_' . $client->id .
    '/docs/' . $client->{$fieldName} . '?' . time();
}


$client = $app->db->getFirst( 'clients', $id );
if ( ! $client ) respond_with( "Client id=$id not found.", 404 );
debug_log( $client, 'Showing detail for client: ', 3 );


// Related data

$year = date( 'Y' );
$lastYear = $year - 1;

$clientModel = new ClientModel( $app );
$clientStateModel = new ClientStateModel( $app );

$clientState = $clientStateModel->getCurrentState( $client );

$client->tccs_current_year = $clientModel->getClientTccPins( $client, ['type' => 'ClientDetailView', 'year' => $year] );
$tccsSummaryCurrent = new TccsSummary( $client->tccs_current_year );

$client->trades_current_year = $clientModel->getClientTrades( $client, ['type' => 'All', 'year' => $year] );
$tradesSummaryCurrent = new TradesSummary( $client->trades_current_year );
$client->trades_last_year = $clientModel->getClientTrades( $client, ['type' => 'All', 'year' => $lastYear] );
$tradesSummaryLastYear = new TradesSummary( $client->trades_last_year );

$ncrs = $app->db->table( 'ch_ncrs' )->getLookupBy( 'id', 'name' );
debug_log( $ncrs, 'NCRS lookup: ', 4 );

$referrers = $app->db->table( 'ch_referrers' )->getLookupBy( 'id', 'name' );
debug_log( $referrers, 'Referrers lookup: ', 4 );

$users = $app->db->table( 'users' )->getLookupBy( 'user_id', 'first_name, last_name', 
  function( $row ) { return $row->first_name . ' ' . $row->last_name; } );
debug_log( $users, 'Users lookup: ', 4 );

$spouse = $app->db->select( 'name' )->getFirst( 'clients', $client->spouse_id );
$spouseName = $spouse ? $spouse->name : null;