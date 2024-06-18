<?php /* Admin Module - Settings SPA - Countries Sub Controller */

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

    if ( $action === 'deleteCountry' )
    {
      $db->pdo->beginTransaction();

      $db->table('loc_countries')->delete( $id );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Country deleted.' ] );

    } // deleteCountry



    /** ACTION 2 **/

    if ( $action === 'saveCountry' )
    {
  
      $isNew = ( $id === 'new' );
      $isEdit = ! $isNew;

      $report = [];
      if ( $isEdit ) $report['id'] = $id;
      $report['name'] = $_POST['name'] ?? 'name';
    
      $db->pdo->beginTransaction();

      $result = $db->table( 'loc_countries' )->save( $report, ['autoStamp' => true, 'user' => $uid ] );
      debug_log( $result, 'Save Country - Result: ' );
      
      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Country saved.' ] );

    } // saveCountry



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

  $countryId = $_GET['id'] ?? null;
  if ( empty( $countryId ) ) respond_with( 'Bad request', 400 );
  $Countries = $db->getFirst( 'loc_countries', $countryId );
  json_response( [ 'success' => true, 'data' => $Countries ] );

}


$form = new AppForm();
$countries = $db->table( 'loc_countries' )->orderBy('name')->getAll();
