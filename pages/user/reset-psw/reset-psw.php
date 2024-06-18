<?php /* User - Reset Password - Controller */

use App\Services\AppView;
use App\Services\AppMailer;


// -------------
// -- REQUEST --
// -------------

$token = $_GET['auth'] ?? null;



// ----------
// -- POST --
// ----------

// CREATE TABLE `sys_tokens` (
//   `id` int(11) NOT NULL AUTO_INCREMENT,
//   `email` varchar(255) NOT NULL,
//   `token` varchar(255) NOT NULL,
//   `ip` varchar(45) DEFAULT NULL,
//   `user_agent` text,
//   `used_at` datetime DEFAULT NULL,
//   `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
//   PRIMARY KEY (`id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

while ( $app->request->isPost )
{

  debug_log( $_GET, 'GET: ', 3 );

  try {

    $now = time();

    $timestamp = $_POST['timestamp'] ?? null;

    // Validate timestamp
    $timestamp = filter_var( $timestamp, FILTER_VALIDATE_INT );

    debug_log( $timestamp, 'Timestamp: ', 3 );


    if ( ! $timestamp )
    {
      $feedback = 'Invalid request';
      break;
    }

    // Minimize DOS attacks by checking if timestamp is less than 1 second ago
    if ( $now - $timestamp < 1 )
    {
      $feedback = 'Invalid request. Please try again.';
      break;
    }

    $newPassword = $_POST['password'] ?? null;
    $confirmPassword = $_POST['confirm_password'] ?? null;

    if ( ! $newPassword )
    {
      $feedback = 'Please enter a new password';
      break;
    }

    if ( $newPassword !== $confirmPassword )
    {
      $feedback = 'Passwords do not match';
      break;
    }

    debug_log( $token, 'Token: ', 3 );

    // Debug the password, but only the first 2 chars    
    $newPasswordDebug = substr( $newPassword, 0, 2 ) . '***';
    debug_log( $newPasswordDebug, 'New Password: ', 3 );
    $confirmPasswordDebug = substr( $confirmPassword, 0, 2 ) . '***';
    debug_log( $confirmPasswordDebug, 'Confirm Password: ', 3 );


    // Validate min length
    if ( strlen( $newPassword ) < 6 )
    {
      $feedback = 'Password must be at least 6 characters long';
      break;
    }


    $db = use_database();

    // Manage token. No email available, only the token
    $passwordReset = $db->table( 'sys_tokens' )->where( 'token', '=', $token )->getFirst();

    if ( ! $passwordReset )
    {
      $feedback = 'Invalid token. Please request a new one.';
      break;
    }

    // Check if token has expired
    $tokenCreatedAt = strtotime( $passwordReset->created_at );
    $tokenExpiresAt = $tokenCreatedAt + 3600; // 1 hour
    $now = time();

    if ( $now > $tokenExpiresAt )
    {
      $feedback = 'Token has expired. Please request a new one.';
      break;
    }

    // Check if token has already been used
    if ( $passwordReset->used_at )
    {
      $feedback = 'Token has already been used. Please request a new one.';
      break;
    }

    debug_log( $passwordReset, 'Password Reset: ', 3 );

    // Get user by email
    $user = $db->table( 'users' )->where( 'email', '=', $passwordReset->email )->getFirst();

    $noPasswordUser = $user ? array_merge( (array) $user, [ 'password' => '***' ] ) : 'Not found';
    debug_log( $noPasswordUser, 'User: ', 3 );

    if ( ! $user )
    {
      $feedback = 'Unknown user: ' . escape( $email );
      break;
    }

    if ( $user->status != 'active' )
    {
      $feedback = 'Account not active. Please contact support.';
      break;
    }

    // Hash new password
    $newPasswordHash = password_hash( $newPassword, PASSWORD_DEFAULT );


    $db->pdo->beginTransaction();

    $now = date( 'Y-m-d H:i:s' );

    // Update token as used
    $db->table( 'sys_tokens' )
      ->update( [ 'id' => $passwordReset->id, 'used_at' => $now ] );

    // Update user password
    $db->table( 'users' )
      ->update( [ 'id' => $user->id, 'password' => $newPasswordHash, 'updated_at' => $now ] );

    // Send email
    $mailer = new AppMailer( $app );

    $emailTemplate = <<<EOT
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .header {
      font-size: 24px;
      font-weight: bold;
      color: #333333;
      margin-bottom: 20px;
    }
    .content {
      font-size: 16px;
      color: #666666;
      line-height: 1.5;
    }
    .content a {
      color: #0066cc;
      text-decoration: none;
    }
    .footer {
      margin-top: 32px;
      font-weight: bold;
      color: #333333;
    }
    a {
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">Password Reset</div>
    <div class="content">
      <p>Hi $user->first_name,</p>
      <p>
        Your password has been reset successfully.
      </p>
    </div>
    <div class="footer">
      <p>Thank you,<br>The Currency Hub Team</p>
    </div>
  </div>
</body>
</html>
EOT;

    $emailResponse = $mailer->send(
      $user->email,
      'Password Reset Successful',
      $emailTemplate,
      null,
      [
        'From' => __SMTP_USER__,
        'FromName' => 'My Currency Hub',
        'Bcc' => 'neels@blackonyx.co.za',
        'BccName' => 'Neels',
      ]
    );

    if ( ! $emailResponse )
    {
      $feedback = 'Failed to send reset success email. Please contact support.';
      break;
    }


    $db->pdo->commit();


    // Redirect to confirmation page
    redirect( '/user/reset-psw/reset-success' );
    exit;

  }

  catch ( Exception $e )
  {
    $db->safeRollback();
    $app->logger->log( $e->getMessage(), 'error' );
    $feedback = 'Oops, something went wrong. Please contact support.';
    break;
  }


  if ( isset( $feedback ) ) debug_log( $feedback, 'Login failed: ', 2 );
  sleep( 2 );

} // End: POST



// ---------
// -- GET --
// ---------

$app->view = new AppView( $app );
$app->view->with( 'title', 'Reset Password');
include $app->view->getFile();
