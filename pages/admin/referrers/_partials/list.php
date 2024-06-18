<?php /* Admin Module - Referrers SPA - Referrers List Sub Controller */


// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) exit;



// ---------
// -- GET --
// ---------

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );


$referrers = $app->db->table( 'ch_referrers' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

debug_log( count( $referrers ), 'Referrers: ', 4 );
