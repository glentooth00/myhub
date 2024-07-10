<?php /* Admin Module - Referrers SPA - Referrers List Sub Controller */


// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) exit;



// ---------
// -- GET --
// ---------

$super = ( $app->user->role == 'super-admin' or $app->user->role == 'sysadmin' );


$beneficiaries = $app->db->table( 'view_beneficiary' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

debug_log( count( $beneficiaries ), 'beneficiaries: ', 4 );
