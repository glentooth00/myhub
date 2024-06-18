<?php /* Admin Module - Dashboard SPA - Show Sub Controller */


// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) { exit; }



// ---------
// -- GET --
// ---------

$super = in_array( $app->user->role,  [ 'super-admin', 'sysadmin' ] );