<?php /* Admin Module - Referrers SPA - Referrer Details Sub Controller */

use App\Models\Client as ClientModel;


// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? null;

if ( ! is_numeric( $id ) ) respond_with( 'Bad request', 400 );



// ---------
// -- GET --
// ---------

$referrer = $app->db->getFirst( 'ch_referrers', $id );
if ( ! $referrer) respond_with( "Referrer id=$id not found.", 404 );

$clientModel = new ClientModel( $app );
$clients = $clientModel->getAllByReferrer( $id );

debug_log( $referrer, 'Showing detail for referrer: ', 3 );