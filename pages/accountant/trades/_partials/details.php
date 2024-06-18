<?php /* Accountant SPA - Trade Details - Sub Controller */

use App\Exceptions\ValidationException;


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
  
  debug_log( $_FILES, 'IS POST Request - FILES: ', 3 );

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

$trade = $app->db->getFirst( 'trades', $id );

$trade->client = $app->db->table( 'clients' )
  ->where( 'client_id', '=', $trade->client_id )
  ->getFirst();