<?php /* Admin Module - Users SPA - User Add/Edit Sub Controller */

use App\Models\User as UserModel;

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
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 2 );


    /** ACTION 1 **/

    if ( $action == 'saveUser' ) {

      $app->db->pdo->beginTransaction();
      debug_log( 'Save User: Begin Transaction', '', 2 );

      $uid = $_POST['user_id'] ?? null;
      if ( ! $uid ) throw new ValidationException( [ 'user_id' => 'User UID is required' ] );

      $firstName = $_POST['first_name'] ?? null;
      if ( ! $firstName ) throw new ValidationException( [ 'first_name' => 'First name is required' ] );

      $role = $_POST['role_id'] ?? null;
      if ( ! $role ) throw new ValidationException( [ 'role_id' => 'Role is required' ] );

      $username = $_POST['username'] ?? null;
      if ( ! $username ) throw new ValidationException( [ 'username' => 'Username is required' ] );

      $userModel = new UserModel( $app );
      $user = $userModel->getUserByUid( $uid );

      if ( $isNew and $user ) throw new ValidationException( [ 'user_id' => 'User UID already exists' ] );

      if ( $isEdit and ! $user ) throw new ValidationException( [ 'user_id' => "User UID = $uid not found" ] );

      if ( $user and $user->id != $id ) throw new ValidationException( [ 'user_id' => 'User UID already exists' ] );

      $password = $_POST['password'] ?? null;
      $confirmPassword = $_POST['confirm_password'] ?? null;

      if ( $password or $confirmPassword ) {
        if ( $password != $confirmPassword ) throw new ValidationException( [ 'password' => 'Confirmation does not match' ] );
        $_POST['password'] = password_hash( $password, PASSWORD_DEFAULT );
        unset( $_POST['confirm_password'] );
      }

      $saveResult = $userModel->save( $_POST );

      $savedUserId = $saveResult['id'] ?? null;

      if ( ! $savedUserId ) throw new Exception( 'User not saved' );

      $app->db->pdo->commit();
      debug_log( 'Save User: Commit Transaction', '', 2 );

      json_response( [ 'success' => true, 'id' => $savedUserId, 'goto' => 'back' ] );

    } // saveUser



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
    $app->db->safeRollBack();
    abort_uploads();
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


$userModel = new UserModel( $app );
$user = $userModel->getUserById( $id );

$statuses = [ 'active', 'inactive' ];
$roles = $app->db->table( 'sys_roles' )->getAll();

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );

if ( ! $super ) {
  $roles = array_filter( $roles, function( $role ) {
    return $role->name != 'super-admin' and $role->name != 'sysadmin' and $role->name != 'system-bot';
  });
}