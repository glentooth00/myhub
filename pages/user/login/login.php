<?php /* global $app */

/**
 * login.php
 * 
 * LOGIN PAGE - Controller
 * 
 * @author: C Moller <xavier.tnc@gmail.com>
 * @date: 23 October 2023
 * 
 * @version: 2.1.0 - 20 Feb 2024
 *   - Remove password from logs.
 *   - Only save relevant fields in the user object.
 * 
 */

if ( __MAINTENANCE_MODE__ && ! isset( $_GET['maint-override'] ) )
{
 include __DIR__ . __DS__ . 'maintmode.html';
 exit;
}


$security = use_security();


while ( $app->request->isPost )
{
  $username = $_POST['username'] ?? null;
  $password = $_POST['password'] ?? null;

  if ( ! $username || ! $password )
  {
    $feedback = 'Please enter a username and password';
    break;
  }

  try {

    $db = use_database();
    $user = $db->table( 'users' )->where( 'username', '=', $username )->getFirst();
    $noPasswordUser = $user ? array_merge( (array) $user, [ 'password' => '***' ] ) : 'Not found';
    debug_log( $noPasswordUser, 'User: ', 3 );

    if ( ! $user )
    {
      $feedback = 'Invalid login credentials';
      break;
    }

    if ( $user->status != 'active' )
    {
      $feedback = 'Account not active. Please contact support.';
      break;
    }

    $roleInfo = $db->getFirst( 'sys_roles', $user->role_id );
    debug_log( $roleInfo, 'roleInfo: ', 3 );

    // $user->role = Virtual field. Required in security->login().
    $user->role = $roleInfo->name;

    if ( $security->login( $user, $username, $password ) )
    {
      $userUpdate = [
        'id' => $user->id,
        'last_login_at' => date('Y-m-d H:i:s')
      ];

      // TODO: Not sure if we still need "home" in the user object... Remove?
      if ( $user->home != $roleInfo->home ) {
        $user->home = $roleInfo->home;
        $userUpdate['home'] = $user->home;
      }
      
      $db->table( 'users' )->save( $userUpdate );
      debug_log( $user->home, 'Login Successful! Redirect to: ', 2 );
      redirect( $user->home );
      exit;
    }

    // Login failed...

    $userUpdate = [
      'id' => $user->id,
      'last_login_at' => date('Y-m-d H:i:s'),
      'failed_logins' => intval( $user->failed_logins ) + 1
    ];

    $db->table( 'users' )->save( $userUpdate );

    $feedback = 'Invalid login credentials';
    break;

  }

  catch ( Exception $e )
  {
    $app->logger->log( $e->getMessage(), 'error' );
    $feedback = 'Login Error: Please contact support.';
    break;
  }

}

if ( isset( $feedback ) ) debug_log( $feedback, 'Login failed: ', 2 );

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Login</title>
  <meta charset="UTF-8">
  <base href="<?=$app->baseUri?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    * { box-sizing: border-box; }
    body { display: flex; background: #f3f3f3; font-family: 'Arial', sans-serif; flex-direction: column; 
      justify-content: center; align-items: center; min-height: 80vh; }
    a, a:visited { text-decoration: none; color: slategrey; }
    a:hover, a:active { color: #007bff; }
    h2 { text-align: center; margin: 0 0 0.67em; }
    main { position: relative; }
    form { background: #fff; padding: 3rem 2rem; border-radius: 5px; box-shadow: 0 0 10px 0 #00000033; }
    label { display: none; }
    input { display:block; width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; }
    button { display: flex; justify-content: center; align-items: center; width: 100%; padding: 10px; background: #333;
      color: #fff; border: none; border-radius: 5px; cursor: pointer; transition: background 0.3s; position: relative; }
    button:hover { background: #555; }
    footer { display: block; text-align: right; position: absolute; color: cadetblue; font-size: 9px; bottom: 5px; right: 5px; }
    .container { width: 300px; }
    .links { margin-bottom: 0; width: 100%; }
    .links a { display: block; margin-bottom: 7px; }  
    .spinner { display: none; margin-right: 10px; border: 4px solid #fff; border-top: 4px solid #999;
      border-radius: 50%; width: 15px; height: 15px; animation: spin 2s linear infinite; }
    .busy .spinner { display: inline-block; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .feedback { font-size: 0.85em; color: crimson; position: relative; top: -0.67em; margin: 0; }
    #passwordFeedback, #usernameFeedback { display: none; }
  </style>
</head>
<body onunload="document.body.className='done'">
  <div class="container">
    <main>
      <h2>My Currency Hub</h2>
      <?php $security->renderLogin( $feedback ?? null ); ?>
      <footer><?=$app->ver?></footer>
    </main>
    <p class="links">
      <!-- <a href="user/register"><small>New? Register here.</small></a> -->
      <a href="user/lost-psw"><small>Forgot password?</small></a>
    </p>
  </div>
</body>
</html>