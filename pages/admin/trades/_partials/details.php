<?php /* Admin Module - Trades SPA - Trade Details Sub Controller */

global $app;

use App\Models\Trade as TradeModel;



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

    if ( $action === 'deleteTrade' ) {

      $tradeModel = new TradeModel( $app );

      $deleteType = $_POST['deleteType'] ?? '';
      $isPermanent = ( $deleteType === 'Permanently Delete' );

      debug_log( "Start $deleteType Trade id=$id", '', 2 );

      // hard delete & exit

      if ( $isPermanent ) {
        $tradeModel->delete( $id );
        json_response( [ 'success' => true, 'id' => $id, 'goto' => 'back' ] );
      }

      // soft delete & remote update S2

      $app->db->pdo->beginTransaction();

      $tradeModel->softDelete( $id );

      $trade = $app->db->select( 'id, trade_id' )->getFirst( 'trades', $id );
      if ( ! $trade ) throw new Exception( 'Trade to delete not found.' );
      debug_log( $trade, 'Trade to delete (before S2 rpc): ', 2 );

      run_google_script( 'deleteRow', [ 'sheetName' => __GOOGLE_TRADES_SHEET_NAME__, 
        'primaryKey' => 'Trade ID', 'primaryKeyValue' => $trade->trade_id ],
        'neels@currencyhub.co.za' );

      $app->db->pdo->commit();

      json_response( [ 'success' => true, 'id' => $id ] );

    } // deleteTrade



    /** ACTION 2 **/

    if ( $action === 'unSoftDeleteTrade' ) {

      $tradeModel = new TradeModel( $app );

      $app->db->pdo->beginTransaction();

      $tradeModel->unSoftDelete( $id );

      debug_log( "Undelete trade id=$id successful!", '', 2 );

      // remote update S2

      $trade = $app->db->select( 'id, trade_id' )->getFirst( 'trades', $id );
      if ( ! $trade ) throw new Exception( 'Trade to undelete not found.' );
      debug_log( $trade, 'Trade to undelete (before S2 rpc): ', 2 );

      run_google_script( 'undeleteRow', [ 'sheetName' => __GOOGLE_TRADES_SHEET_NAME__, 
        'primaryKey' => 'Trade ID', 'primaryKeyValue' => $trade->trade_id ],
        'neels@currencyhub.co.za' ); 

      $app->db->pdo->commit();

      json_response( [ 'success' => true, 'id' => $id ] );

    } // unSoftDeleteTrade



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

$trade = $app->db->getFirst( 'trades', $id );

if ( ! $trade ) die( "Error: Invalid trade id=$id." );

$trade->client = $app->db->table( 'clients' )
  ->where( 'client_id', '=', $trade->client_id )
  ->getFirst();

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );