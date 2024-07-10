<?php /* Admin Module - Referrers SPA - Referrer Add/Edit Sub Controller */

use App\Models\Template as TemplateModel;

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

    if ( $action == 'saveTemplate' ) {

      $id = $_POST['id'] ?? null;
      if ( $isEdit and ! $id ) throw new ValidationException( [
          'id' => 'ID is required' ] );

      $name = $_POST['name'] ?? null;
      if ( ! $name ) throw new ValidationException( [
        'name' => 'Name name is required' ] );

      $model_type_id = $_POST['model_type_id'] ?? null;
      if ( ! $model_type_id ) throw new ValidationException( [
          'model_type_id' => 'Model Type ID name is required' ] );

      $templateModel = new TemplateModel( $app );
      $template = $templateModel->getTemplateById( $id );

      $saveResult = $templateModel->save( $_POST );

      $savedTemplateId = $saveResult['id'] ?? null;

      if ( ! $savedTemplateId ) throw new Exception( 'Template not saved' );

      json_response( [ 'success' => true, 'id' => $savedTemplateId, 'goto' => 'back' ] );

    } // saveTemplate



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

$templateModel = new TemplateModel( $app );
$templates = $templateModel->getTemplateById( $id );

$types = $app->db->table( 'ch_beneficiaries_types' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );