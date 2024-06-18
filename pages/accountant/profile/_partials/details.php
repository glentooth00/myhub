<?php /* Accountant SPA - Profile Details -  Sub Controller */

use App\Models\User as UserModel;

use App\Exceptions\ValidationException;



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

      // Check that post userid == session userid
      $userId = $_POST['userId'] ?? null;
      if ( ! $userId or $userId != $app->user->id ) throw new Exception( 'Bad request' );

      // Validate passwords
      $oldPassword = $_POST['oldPassword'] ?? null;
      $newPassword = $_POST['newPassword'] ?? null;
      $confirmPassword = $_POST['confirmPassword'] ?? null;
      if ( ! $oldPassword or ! $newPassword or ! $confirmPassword )
        throw new ValidationException( [ 'password' => 'All fields are required.' ] );

      $app->db->pdo->beginTransaction();

      $userModel = new UserModel( $app );
      $result = $userModel->changePassword( $oldPassword, $newPassword, $confirmPassword );
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

$user = $app->db->getFirst( 'users', $app->user->id );