<?php /* Admin Module - Referrers SPA - Referrer Add/Edit Sub Controller */

use App\Models\Referrer as ReferrerModel;

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
  debug_log( $app->user, 'IS POST Request - Referrer: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 2 );


    /** ACTION 1 **/

    if ( $action == 'saveReferrer' ) {

      $uid = $_POST['referrer_id'] ?? null;
      if ( ! $uid ) throw new ValidationException( [
       'referrer_id' => 'Referrer UID is required' ] );

      $name = $_POST['name'] ?? null;
      if ( ! $name ) throw new ValidationException( [
        'name' => 'Name name is required' ] );

      $referrerModel = new ReferrerModel( $app );
      $referrer = $referrerModel->getReferrerByUid( $uid );

      if ( $isNew and $referrer ) throw new ValidationException( [
        'referrer_id' => 'Referrer UID already exists' ] );

      if ( $isEdit and ! $referrer ) {
        $referrer = $app->db->getFirst( 'ch_referrers', $id );
        if ( ! $referrer ) throw new ValidationException( [
          'id' => "Referrer ID = $id not found" ] );
      }

      if ( $referrer and $referrer->id != $id ) throw new ValidationException( [
       'referrer_id' => 'Referrer UID already exists' ] );


      if ( empty( $_POST['user_id'] ) ) $_POST['user_id'] = $uid;


      $saveResult = $referrerModel->save( $_POST );

      $savedReferrerId = $saveResult['id'] ?? null;

      if ( ! $savedReferrerId ) throw new Exception( 'Referrer not saved' );

      json_response( [ 'success' => true, 'id' => $savedReferrerId, 'goto' => 'back' ] );

    } // saveReferrer



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


$referrerModel = new ReferrerModel( $app );
$referrer = $referrerModel->getReferrerById( $id );

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );