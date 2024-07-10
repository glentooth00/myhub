<?php /* Admin Module - Tools Batch Process SPA - Clients Sub Controller */

use App\Models\ClientState as ClientStateModel;



// -------------
// -- REQUEST --
// -------------

$operationId = $_GET['op'] ?? null;
if ( empty( $operationId ) ) respond_with( 'Bad request', 400 );



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {


    $action = $_POST['action'] ?? '';
    $mode = $_POST['mode'] ?? 'test';

    debug_log( $action, 'IS POST Request - Action: ', 3 );

    $uid = $app->user->user_id;

    $db = use_database();
    $app->db = $db;



    /** ACTION 1 **/

    if ( $action === 'fetchClients' )
    {

      $query = $_POST['query'] ?? '';
      if ( ! $query ) json_response( [ 'success' => false, 'message' => 'Invalid query' ] );

      // Check if query is an SQL Statement
      if ( ! preg_match( '/^SELECT/', $query ) ) {
        // Maybe it's a list of client IDs seperated by line breaks or commas
        $list = str_replace( [ "\r", "\n" ], ',', $query );
        $list = preg_replace( '/\s*,\s*/', ',', $list );
        $list = explode( ',', $list );
        $list = array_map( 'trim', $list );
        $list = array_filter( $list, 'strlen' );
        $list = array_map( function( $item ) { return "'$item'"; }, $list );
        $list = implode( ',', $list );
        debug_log( $list, 'Client IDs List: ', 3 );
        $query = "SELECT id,client_id,name,status FROM clients WHERE client_id IN ($list)";
      }

      $results = $db->query( $query );

      $response = [ 'success' => true, 'message' => 'Fetch Clients Ok', 'data' => $results ];

      debug_log( $response, 'Fetch Clients Response: ', 3 );

      json_response( $response );

    } // fetchClients



    /** ACTION 2 **/

    if ( $action === 'updateClientState' )
    {

      $db->pdo->beginTransaction();

      $clientId = $_POST['clientId'] ?? null;

      $client = $app->db->getFirst( 'clients', $clientId );
      if ( ! $client ) throw new Exception( "Client uid=$client->client_id not found." );
      debug_log( $client, 'Client to update: ', 2 );

      $fiaUsedBefore = $client->fia_used;

      $clientStateModel = new ClientStateModel( $app );
      $updateResult = $clientStateModel->updateStateFor( $client );
      debug_log( $updateResult, 'Client Update State Result: ', 3 );

      $db->pdo->commit();

      json_response( [ 'success' => true,
        'message' => "$client->client_id: FIA Used: $fiaUsedBefore / $client->fia_used" ] );

    } // updateClientStats



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $app->logger->log( $ex->getMessage(), 'error' );
    $db->safeRollBack();
    $file = $ex->getFile();
    $line = $ex->getLine();
    $message = $ex->getMessage();
    $message .= "<br>---<br>Error on line: $line of $file";
    json_response( [ 'success' => false, 'message' => $message ] );
  }

}



// ---------
// -- GET --
// --------- 