<?php /* Admin Module - Users SPA - Users List Sub Controller */ 

use App\Models\UserSettings as UserSettingsModel;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) exit;



// ---------
// -- GET --
// ---------

/* settings */
$settings = new UserSettingsModel( $app );


/* request */
$status = $_GET['status'] ?? $settings->getSettingValue( 'users_status', 'active' );

$settings->saveIfChanged( 'users_status', $status );


$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );

$q = $app->db->table( 'users' );

if ( ! $super ) {
	$q->where( 'role_id', 'NOT IN', [1,2,3] );
}

if ( $status and $status != 'all' ) {
	$q->where( 'status', $status );
}

$users = $q->getAll();

$roles = $app->db->table( 'sys_roles' )->getLookupBy( 'id', 'description' );
debug_log( $roles, 'Roles lookup: ', 4 );
