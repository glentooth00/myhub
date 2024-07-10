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

$revenue = $app->db->getFirst( 'ch_revenue_models', $id );
if ( ! $revenue) respond_with( "Revenue id=$id not found.", 404 );

// $beneficiariesType = new BeneficiariesTypeModel( $app );
// $beneficiaries_type = $beneficiariesType->getAllByBeneficiariesType( $id );

$type = $app->db->table('ch_beneficiaries_types')
    ->where('id', '=', $revenue->type_id)
    ->getFirst();

$type_name = $type ? $type->name : 'Unknown Type';

$clients = $app->db->table('clients')
    ->where('id', '=', $revenue->client_id)
    ->getFirst();

$client_name = $clients ? $clients->name : 'Unknown Type';

$referrer = $app->db->table('ch_referrers')
    ->where('id', '=', $revenue->referrer_id)
    ->where('deleted_at', 'IS', null)
    ->getFirst();

$referrer_name = $referrer ? $referrer->name : 'Unknown Referrer';

$bene_model = $app->db->table( 'view_beneficiary_revenue_model' )
  ->where( 'updated_at', 'IS', null )
  ->where( 'revenue_model_id', '=', $id )
  ->getAll();
  

debug_log( $revenue, 'Showing detail for Revenue: ', 3 );