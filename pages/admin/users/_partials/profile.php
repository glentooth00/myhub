<?php /* Admin Module - Users SPA - User Profile Sub Controller */

use App\Models\User as UserModel;

use App\Exceptions\ValidationException;



// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? null;

if ( ! is_numeric( $id ) ) $id = $app->user->id;

$super = ( $app->user->role == 'sysadmin' or $app->user->role == 'super-admin' );

$isMyProfile = ( $id == $app->user->id );




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

    if ( $action === 'changePassword' ) {

      // Validate user
      debug_log( $super, 'IS POST Request - super: ', 2 );
      debug_log( $isMyProfile, 'IS POST Request - isMyProfile: ', 2 );
      if ( ! $isMyProfile and ! $super ) throw new Exception( 'Bad request' );

      // Validate passwords
      $newPassword = $_POST['newPassword'] ?? null;
      $confirmPassword = $_POST['confirmPassword'] ?? null;

      $app->db->pdo->beginTransaction();

      $userModel = new UserModel( $app );
      $result = $userModel->changePassword( null, $newPassword, $confirmPassword, 'isadmin' );
      if ( ! $result ) throw new Exception( 'Failed to update password.' );

      $app->db->pdo->commit();

      json_response( [ 'success' => true, 'message' => 'Password successfully updated.' ] );

    } // changePassword



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( ValidationException $ex ) {
    $resp['success'] = false;
    $resp['errors'] = $ex->getErrors();
    $resp['message'] = $ex->getMessage();
    debug_log( $resp, 'Validation Exception: ' );
    $app->db->safeRollBack();
    json_response( $resp );
  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    $app->db->safeRollBack();
    json_response( [ 'success' => false, 'message' => $error ] );
  }

}  



// ---------
// -- GET --
// ---------

$user = $app->db->getFirst( 'users', $id );
if ( ! $user ) respond_with( 'User not found', 404 );

$roles = $app->db->table( 'sys_roles' )->getLookupBy( 'id', 'description' );
debug_log( $roles, 'Roles lookup: ', 4 );