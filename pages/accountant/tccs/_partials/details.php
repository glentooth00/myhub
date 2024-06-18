<?php /* Accountant SPA - TCC Details - Sub Controller */

use App\Models\Tcc as TccModel;


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

    if ( $action === 'deleteTcc' ) {

      $tccModel = new TccModel( $app );

      $deleteType = $_POST['deleteType'] ?? '';
      $isPermanent = ( $deleteType === 'Permanently Delete' );

      debug_log( "Start $deleteType TCC id=$id", '', 2 );      

      // hard delete & exit

      if ( $isPermanent ) {
        $tccModel->delete( $id );
        json_response( [ 'success' => true, 'id' => $id, 'goto' => 'back' ] );
      }

      // soft delete & remote update S2

      $app->db->pdo->beginTransaction();      

      $tccModel->softDelete( $id );

      $tcc = $app->db->select( 'id, tcc_id' )->getFirst( 'tccs', $id );
      if ( ! $tcc ) throw new Exception( 'TCC to delete not found.' );
      debug_log( $tcc, 'TCC to delete (before S2 rpc): ', 2 );

      run_google_script( 'deleteRow', [ 'sheetName' => __GOOGLE_TCCS_SHEET_NAME__, 
        'primaryKey' => 'TCC ID', 'primaryKeyValue' => $tcc->tcc_id ],
        'neels@currencyhub.co.za' );  

      $app->db->pdo->commit();

      json_response( [ 'success' => true, 'id' => $id ] );

    } // deleteTcc



    /** ACTION 2 **/

    if ( $action === 'unSoftDeleteTcc' ) {

      $tccModel = new TccModel( $app );

      $app->db->pdo->beginTransaction();

      $tccModel->unSoftDelete( $id );

      debug_log( "Undelete TCC id=$id successful!", '', 2 );

      // remote update S2

      $tcc = $app->db->select( 'id, tcc_id' )->getFirst( 'tccs', $id );
      if ( ! $tcc ) throw new Exception( 'TCC to undelete not found.' );
      debug_log( $tcc, 'TCC to undelete (before S2 rpc): ', 2 );

      run_google_script( 'undeleteRow', [ 'sheetName' => __GOOGLE_TCCS_SHEET_NAME__, 
        'primaryKey' => 'TCC ID', 'primaryKeyValue' => $tcc->tcc_id ],
        'neels@currencyhub.co.za' );  

      $app->db->pdo->commit();      

      json_response( [ 'success' => true, 'id' => $id ] );

    } // unSoftDeleteTcc



    /** INVALID ACTION **/

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

}



// ---------
// -- GET --
// ---------

function get_cert_url( $tcc ) {
  global $app;
  return $app->uploadsRef . '/' . $tcc->client_name . '_' . $tcc->client_id2 . '/' . 
    $tcc->tcc_pin . '.pdf?' . time();
}

$tcc = $app->db->getFirst( 'view_tccs', $id );
if ( ! $tcc ) respond_with( "TCC id=$id not found.", 404 );
debug_log( $tcc, 'Showing detail for tcc: ', 3 );