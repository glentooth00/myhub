<?php /* Admin Module - Settings SPA - Suburbs Sub Controller */

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

    if ( $action === 'deleteModelType' )
    {
      $db->pdo->beginTransaction();

      $db->table('ch_revenue_model_types')->delete( $id );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Model Type deleted.' ] );

    } // deleteSuburb



    /** ACTION 2 **/

    if ( $action === 'saveModelType' )
    {
  
      $isNew = ( $id === 'new' );
      $isEdit = ! $isNew;

      $report = [];
      if ( $isEdit ) $report['id'] = $id;
      $report['name'] = $_POST['name'] ?? 'name';
      $report['description'] = $_POST['description'] ?? 'description';
    //   $report['type_id'] = $_POST['type_id'] ?? null;
    
      $db->pdo->beginTransaction();

      $result = $db->table( 'ch_revenue_model_types' )->save( $report, ['autoStamp' => true, 'user' => $uid ] );
      debug_log( $result, 'Save Model-Type - Result: ' );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Model-Type saved.' ] );

    } // saveSuburb



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

  $modelId = $_GET['id'] ?? null;
  if ( empty( $modelId ) ) respond_with( 'Bad request', 400 );
  $model = $db->getFirst( 'ch_revenue_model_types', $modelId );
  json_response( [ 'success' => true, 'data' => $model ] );

}


$form = new AppForm();
$model = $db->table('ch_revenue_model_types')->orderBy('name')->getAll();
