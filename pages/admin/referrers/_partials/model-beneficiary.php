<?php /* Admin Module - Referrers SPA - Referrer Add/Edit Sub Controller */

use App\Models\ModelBeneficiary as ModelBeneficiaryModel;

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

    if ( $action == 'saveModel' ) {

      $id = $_POST['id'] ?? null;
      if ( $isEdit and ! $id ) throw new ValidationException( [
       'id' => 'Model Beneficiary ID is required' ] );

      $revenue_model_id = $_POST['revenue_model_id'] ?? null;
      if ( ! $revenue_model_id ) throw new ValidationException( [
      'revenue_model_id' => 'revenue model id is required' ] );

      $beneficiary_id = $_POST['beneficiary_id'] ?? null;
      if ( ! $beneficiary_id ) throw new ValidationException( [
      'beneficiary_id' => 'beneficiary id is required' ] );

      $revenue_share = $_POST['revenue_share'] ?? null;
      if ( ! $revenue_share ) throw new ValidationException( [
      'revenue_share' => 'Revenue share id is required' ] );

      $beneficiaryModel = new ModelBeneficiaryModel( $app );
      $model = $beneficiaryModel->getModelBeneficiariesById( $id );

      $saveResult = $beneficiaryModel->save( $_POST );

      $savedModelId = $saveResult['id'] ?? null;

      if ( ! $savedModelId ) throw new Exception( 'Model Beneficiary not saved' );

      json_response( [ 'success' => true, 'id' => $savedModelId, 'goto' => 'back' ] );

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

$beneficiaryModel = new ModelBeneficiaryModel( $app );
$model = $beneficiaryModel->getModelBeneficiariesById( $id );

$revenues = $app->db->table( 'ch_revenue_models' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

$beneficiaries = $app->db->table( 'ch_beneficiaries' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );