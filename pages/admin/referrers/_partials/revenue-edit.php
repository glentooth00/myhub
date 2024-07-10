<?php /* Admin Module - Referrers SPA - Referrer Add/Edit Sub Controller */

use App\Models\Revenue as RevenueModel;

use App\Exceptions\ValidationException;



// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? 'new';

if ( ! is_numeric( $id ) and $id !== 'new' )
  respond_with( 'Bad request', 400 );

$isNew = ( $id === 'new' );
$isEdit = ! $isNew;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $_FILES, 'IS POST Request - FILES: ', 2 );
  debug_log( $app->user, 'IS POST Request - Revenue: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 2 );


    /** ACTION 1 **/

    if ( $action == 'saveRevenue' ) {

      $id = $_POST['id'] ?? null;
      if ( $isEdit and ! $id ) throw new ValidationException( [
          'id' => 'Beneficiaries ID is required' ] );

      $name = $_POST['name'] ?? null;
      if ( ! $name ) throw new ValidationException( [
        'name' => 'Name name is required' ] );

      $type_id = $_POST['type_id'] ?? null;
      if ( ! $type_id ) throw new ValidationException( [
          'type_id' => 'Type_id name is required' ] );

      $client_id = $_POST['client_id'] ?? null;
      if ( ! $client_id ) throw new ValidationException( [
          'client_id' => 'Client_id name is required' ] );

      $referrer_id = $_POST['referrer_id'] ?? null;
          if ( ! $referrer_id ) throw new ValidationException( [
          'referrer_id' => 'Referrer_id name is required' ] );

      $revenueModel = new RevenueModel( $app );
      $revenue = $revenueModel->getRevenueById( $id );

      $saveResult = $revenueModel->save( $_POST );

      $savedRevenueId = $saveResult['id'] ?? null;

      if ( ! $savedRevenueId ) throw new Exception( 'Revenue not saved' );

      json_response( [ 'success' => true, 'id' => $savedRevenueId, 'goto' => 'back' ] );

    } // saveRevenue



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( ValidationException $ex ) {
    $resp['success'] = false;
    $resp['errors'] = $ex->getErrors();
    $resp['message'] = $ex->getMessage();
    debug_log( $resp, 'Validation Exception: ' );
    json_response( $resp );
  }

  catch ( Exception $ex ) {
    $resp['success'] = false;
    $resp['message'] = $ex->getMessage();
    $app->logger->log( $resp['message'], 'error' );
    json_response( $resp );
  }

}



// ---------
// -- GET --
// ---------

function renderOption( $value, $label = null, $selectedValue = null )
{
  $selected = ( $value == $selectedValue ) ? ' selected' : '';
  return "<option value='$value'$selected>" . ($label ?: $value) . '</option>' . PHP_EOL;
}

$revenueModel = new RevenueModel( $app );
$revenue = $revenueModel->getRevenueById( $id );

$types = $app->db->table( 'ch_beneficiaries_types' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

$clients = $app->db->table( 'clients' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

$referrers = $app->db->table( 'ch_referrers' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );