<?php /* API Endpoint Controller: "api/v1/clients/tradesheet" */

require __DIR__ . '/../../api.php';


// Remember, we log the requester IP and Agent in "api.php",
// so we do not have to do it here.
debug_log( 'API endpoint: "api/v1/clients/tradesheet" says hi!  ' .
  'Request type = ' . $_SERVER['REQUEST_METHOD'], '', 2 );



// ---------
// -- CLI --
// ---------

if ( $app->request->cli ) respond_with( 'Bad request', 400 );



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );
    
  $jsonData = file_get_contents( 'php://input' );
  $_POST = json_decode( $jsonData, true ); 
  
  debug_log( $_POST, 'API POST Request. Data = ', 3 );

  use_database();
  debug_log( 'Database connected.', '', 3 );

  try {

    $action = $_POST['action'] ?? null;
    debug_log( $action, 'POST Action: ', 2 );
      
    if ( $action !== 'provideClientStats' ) {
      respond_with( 'Bad request', 400 );
    }

    $clientUIDs = $_POST['clients'] ?? [];
    
    debug_log( $clientUIDs, 'OK, so let\'s provide client stats for: ', 2 );
    
    $select = 'client_id,trading_capital,sda_mandate,fia_mandate,' .
     'bank,accountant,fia_approved,sda_used,fia_used';
    
    $clients = $app->db->table( 'clients' )
       ->select( $select )
       ->where( 'client_id', 'IN', $clientUIDs )
       ->getAll();
    
    $clientStats = [];
    foreach($clients as $clientData) {
      $clientUID = $clientData->client_id;
      unset( $clientData->client_id );
      $clientStats[ $clientUID ] = array_values( (array) $clientData );
    }
    
    $response = [ 'success' => true, 'stats' => $clientStats ];
    json_response( $response );
    
  }
 
  catch ( Exception $e ) {
    $app->db->safeRollback();
    $error = $e->getMessage();
    $app->logger->log( $error, 'error' );
    $response = [ 'success' => false, 'error' => $error ];
    json_response( $response );      
  }

  exit; // Just in case we forget to exit after a response.

}



// ---------
// -- GET --
// ---------

respond_with( 'Bad request', 400 );