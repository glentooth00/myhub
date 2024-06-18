<?php /* Admin Module - Settings SPA - Provinces Sub Controller */

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

    if ( $action === 'deleteProvince' )
    {
      $db->pdo->beginTransaction();

      $db->table('loc_provinces')->delete( $id );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Province deleted.' ] );

    } // deleteProvince



    /** ACTION 2 **/

    if ( $action === 'saveProvince' )
    {
  
      $isNew = ( $id === 'new' );
      $isEdit = ! $isNew;

      $report = [];
      if ( $isEdit ) $report['id'] = $id;
      $report['name'] = $_POST['name'] ?? 'name';
    
      $db->pdo->beginTransaction();

      $result = $db->table( 'loc_provinces' )->save( $report, ['autoStamp' => true, 'user' => $uid ] );
      debug_log( $result, 'Save Province - Result: ' );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Province saved.' ] );

    } // saveProvince



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

  $provinceId = $_GET['id'] ?? null;
  if ( empty( $provinceId ) ) respond_with( 'Bad request', 400 );
  $Provinces = $db->getFirst( 'loc_provinces', $provinceId );
  json_response( [ 'success' => true, 'data' => $Provinces ] );

}


$form = new AppForm();
$provinces = $db->table( 'loc_provinces' )->orderBy('name')->getAll();

