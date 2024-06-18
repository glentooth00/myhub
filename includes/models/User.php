<?php namespace App\Models;

use stdClass;
use Exception;

use App\Exceptions\ValidationException;


class User {

  private $app;

  public $table;
  public $view;


  function __construct( $app, $table = 'users', $view = 'view_users' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function getNewUser()
  {
    $user = new stdClass();
    $user->id = null;
    $user->user_id = substr( uniqid(), -8 );
    $user->role_id = null;
    $user->username = null;
    $user->password = null;
    $user->first_name = null;
    $user->last_name = null;
    $user->email = null;
    $user->status = null;
    $user->home = null;
    $user->created_at = null;
    $user->created_by = null;
    $user->updated_at = null;
    $user->updated_by = null;
    $user->last_login_at = null;
    $user->last_activity_at = null;
    $user->failed_logins = null;
    $user->verification_token = null;
    return $user;
  }


  public function getUserByUid( $UID )
  {
    debug_log( $UID, 'getUserByUid(), UID = ', 2 );
    $result = $this->app->db->table( $this->table )->where( 'user_id', $UID )->getFirst();
    return $result;
  }


  public function getUserById( $id = 'new' )
  {
    debug_log( $id, 'getUserById(), id = ', 2 );
    $isEdit = ( $id !== 'new' and $id > 0 );
    $result = $isEdit ? $this->app->db->getFirst( $this->table, $id ) : $this->getNewUser();
    return $result;
  }


  public function getUsersByRole( $role )
  {
    debug_log( $role, 'User::getUsersByRole() ', 4 );
    $table = 'users as u LEFT JOIN sys_roles as r on (u.role_id = r.id)';
    $users = $this->app->db->table( $table ) 
      ->select( 'u.id, u.user_id, u.username, u.first_name, u.last_name, u.email, r.name as role, r.home' )
      ->where( 'r.name', '=', $role )
      ->orderBy( 'u.first_name, u.last_name' )
      ->getAll();
    debug_log( $users, 'users: ', 3 );
    return $users;
  }


  public function getInternalUsers()
  {
    debug_log( 'User::getInternalUsers() ', '', 4 );
    $users = $this->app->db->table( 'users' )
     ->where( 'user_id', '!=', '_cron_' )
     ->orderBy( 'first_name, last_name' )
     ->getAll();
    return $users;
  }


  public function changePassword( $oldPassword, $newPassword, $confirmPassword = null, $isAdmin = false )
  {
    if ( ( ! $oldPassword and ! $isAdmin ) or ! $newPassword or ! $confirmPassword )
      throw new ValidationException( [ 'password' => 'All fields are required.' ] );

    if ( $newPassword != $confirmPassword )
      throw new ValidationException( [ 'password' => 'Confirmation does not match.' ] );

    $userData = $this->app->db->getFirst( $this->table, $this->app->user->id );
    if ( ! $userData ) throw new Exception( 'User not found.' );

    // Check that the old password matches the current password if this is not an Admin request.
    if ( ! $isAdmin and ! password_verify( $oldPassword, $userData->password ) )
      throw new ValidationException( [ 'password' => 'Old password is incorrect.' ] );

    // The second argument, PASSWORD_DEFAULT, is a constant that represents the
    // default hashing algorithm used by PHP. The specific algorithm may vary
    // depending on the PHP version and configuration.
    $newPassword = password_hash( $newPassword, PASSWORD_DEFAULT );

    $updateData = [ 'id' => $userData->id, 'password' => $newPassword ];

    return $this->app->db->table( $this->table )->save( $updateData, 
      [ 'autoStamp' => true, 'user' => $userData->user_id ] );
  }


  public function update( array $data, $options = [] )
  {
    $options['autoStamp'] = $options['autoStamp'] ?? true;
    $user = $options['user'] ?? $this->app->user;
    $options['user'] = $user->user_id;
    $result = $this->app->db->table( $this->table )->update( $data, $options );
    return $result;
  }


  public function save( array $data, $options = [] )
  {
    $options['autoStamp'] = $options['autoStamp'] ?? true;
    $user = $options['user'] ?? $this->app->user;
    $options['user'] = $user->user_id;
    $result = $this->app->db->table( $this->table )->save( $data, $options );
    return $result;
  }  

} // User
