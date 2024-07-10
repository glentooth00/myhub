<?php
/* User - Lost Password - Controller */

use App\Services\AppView;
use App\Services\AppMailer;



// ----------
// -- POST --
// ----------

while ( $app->request->isPost ) {

  $now = time();

  $email = $_POST['email'] ?? null;
  $timestamp = $_POST['timestamp'] ?? null;

  // Validate timestamp
  $timestamp = filter_var( $timestamp, FILTER_VALIDATE_INT );

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

  if ( ! $email ) {
    $feedback = 'Please enter a valid email address';
    break;
  }

  try {

    $db = use_database();

    $user = $db->table( 'users' )->where( 'email', '=', $email )->getFirst();
    $noPasswordUser = $user ? array_merge( (array) $user, ['password' => '***'] ) : 'Not found';
    debug_log( $noPasswordUser, 'User: ', 3 );

    if ( ! $user ) {
      $feedback = 'Unknown user: ' . escape( $email );
      break;
    }

    if ( $user->status != 'active' ) {
      $feedback = 'Account not active. Please contact support.';
      break;
    }

    $token = bin2hex( random_bytes( 16 ) );

    $db->table( 'sys_tokens' )->insert( [
      'email' => $email,
      'token' => $token,
      'ip' => $_SERVER['REMOTE_ADDR'],
      'user_agent' => $_SERVER['HTTP_USER_AGENT'],
      'created_at' => date('Y-m-d H:i:s')
    ]);

    $mailer = new AppMailer( $app );

    $link = full_url( 'user/reset-psw?auth=' . urlencode( $token ) );

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
    .page {
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
  <div class="page">
    <div class="header">Reset Password</div>
    <div class="content">
      <p>Hi there $user->first_name,</p>
      <p>
        You requested to reset your password. If this wasn't you, please contact support.
      </p>
      <p>
        Use the link below to reset your password:<br>
        <a href="$link">$link</a>
      </p>
    </div> <!-- .content -->
    <div class="footer">
      <p>Thank you,<br>The Currency Hub Team</p>
    </div>
  </div> <!-- .page -->
</body>
</html>
EOT;

// def send_simple_message
//   RestClient.post "https://api:YOUR_API_KEY"\
//   "@api.mailgun.net/v3/YOUR_DOMAIN_NAME/messages",
//   :from => "Excited User <mailgun@YOUR_DOMAIN_NAME>"
//   :to => "bar@example.com, YOU@YOUR_DOMAIN_NAME",
//   :subject => "Hello",
//   :text => "Testing some Mailgun awesomeness!"
// end

    $emailResponse = $mailer->send(
      $email,  // 'neels@sandbox540d29c84ca04600ab0369f03170047b.mailgun.org',
      'Reset Password',
      $emailTemplate,
      null,
      [
        'From' => __SMTP_USER__,
        'FromName' => 'My Currency Hub',
        // 'Bcc' => 'neels@blackonyx.co.za',
        // 'BccName' => 'Neels'
      ]
    );

    if ( ! $emailResponse ) {
      $feedback = 'Failed to send reset email. Please contact support.' . PHP_EOL . $mailer->getLastError();
      break;
    }

    redirect( 'user/lost-psw/reset-email-sent' );
    exit;

  } catch ( Exception $e ) {
    $app->logger->log( $e->getMessage(), 'error' );
    $feedback = 'Oops, something went wrong. Please contact support.';
    break;
  }

  if ( isset( $feedback ) ) debug_log( $feedback, 'Reset password failed: ', 2 );
  sleep(2);

} // End: POST



// ---------
// -- GET --
// ---------

$app->view = new AppView( $app );
$app->view->with( 'title', 'Forgot Password' );
include $app->view->getFile();
