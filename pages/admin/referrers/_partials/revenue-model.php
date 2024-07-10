<?php /* Admin Module - Referrers SPA - Referrers List Sub Controller */


// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) exit;



// ---------
// -- GET --
// ---------

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );


$revenue_model = $app->db->table( 'view_revenue_model' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

debug_log( count( $revenue_model ), 'revenue_model: ', 4 );
