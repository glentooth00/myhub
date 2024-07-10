<?php /* Admin Module - Referrers SPA - Referrer Details Sub Controller */

use App\Models\BeneficiariesType as BeneficiariesTypeModel;


// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? null;

if ( ! is_numeric( $id ) ) respond_with( 'Bad request', 400 );



// ---------
// -- GET --
// ---------

$beneficiaries = $app->db->getFirst( 'ch_beneficiaries', $id );
if ( ! $beneficiaries) respond_with( "Beneficiaries id=$id not found.", 404 );

// Fetch the type name based on type_id
$type = $app->db->table('ch_beneficiaries_types')
    ->where('id', '=', $beneficiaries->type_id)
    ->getFirst();

$type_name = $type ? $type->name : 'Unknown Type';

// Fetch the referrer name based on referrer_id
$referrer = $app->db->table('ch_referrers')
    ->where('id', '=', $beneficiaries->referrer_id)
    ->where('deleted_at', 'IS', null)
    ->getFirst();

$referrer_name = $referrer ? $referrer->name : 'Unknown Referrer';

$beneficiary_model = $app->db->table( 'ch_revenue_model_beneficiaries' )
  ->where( 'updated_at', 'IS', null )
  ->getAll();

debug_log( $beneficiaries, 'Showing detail for beneficiaries: ', 3 );