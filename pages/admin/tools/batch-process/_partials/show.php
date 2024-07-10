<?php /* Admin Module - Tools Batch Process SPA - Main Controller */

use App\Services\AppForm;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 3 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );  
  
  try {

    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? '';

    debug_log( $action, 'IS POST Request - Action: ', 3 );

    if ( ! is_numeric( $id ) and $id !== 'new' ) respond_with( 'Bad request', 400 );


    $uid = $app->user->user_id;

    $db = use_database();



    /** ACTION 1 **/

    if ( $action === 'deleteProcessOperation' )
    {
      $db->pdo->beginTransaction();

      $db->table( 'batch_operations' )->delete( $id );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Report deleted.' ] );

    } // deleteProcessOperation



    /** ACTION 2 **/

    if ( $action === 'saveProcessOperation' )
    {
  
      $isNew = ( $id === 'new' );
      $isEdit = ! $isNew;

      $operation = [];
      if ( $isEdit ) $operation['id'] = $id;
      $operation['description'] = $_POST['description'] ?? 'no description';
      $operation['page'] = $_POST['page'] ?? '';
      $operation['type_id'] = $_POST['type_id'] ?? null;
    
      $db->pdo->beginTransaction();

      $saveResult = $db->table( 'batch_operations' )->save( $operation, ['autoStamp' => true, 'user' => $uid ] );
      debug_log( $saveResult, 'Save Report - Result: ' );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Report saved.' ] );

    } // saveProcessOperation



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $app->db->safeRollBack();
    $app->logger->log( $ex->getMessage(), 'error' );
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

$db = use_database();


if ( $app->request->isAjax and $app->request->type !== 'page' ) {

  $operationId = $_GET['id'] ?? null;
  if ( empty( $operationId ) ) respond_with( 'Bad request', 400 );
  $batchOperation = $db->getFirst( 'batch_operations', $operationId );
  json_response( [ 'success' => true, 'data' => $batchOperation ] );

}


$form = new AppForm();
$batchOperations = $db->table( 'batch_operations' )->getAll();
$batchTypes = $db->table( 'batch_types' )->getLookupBy( 'id', 'description' );
