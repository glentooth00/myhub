<?php /* Admin Module - Referrers SPA - Referrers List Sub Controller */


// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) exit;



// ---------
// -- GET --
// ---------

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );


$template = $app->db->table( 'view_template' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

debug_log( count( $template ), 'template: ', 4 );
