<?php /* Admin Module - Referrers SPA - Referrer Details Sub Controller */

use App\Models\Template as TemplateModel;


// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? null;

if ( ! is_numeric( $id ) ) respond_with( 'Bad request', 400 );



// ---------
// -- GET --
// ---------

$templates = $app->db->getFirst( 'ch_revenue_model_templates', $id );
if ( ! $templates) respond_with( "Templates id=$id not found.", 404 );

// Fetch the type name based on type_id
$type = $app->db->table('ch_beneficiaries_types')
    ->where('id', '=', $templates->model_type_id)
    ->getFirst();

$type_name = $type ? $type->name : 'Unknown Type';

debug_log( $templates, 'Showing detail for beneficiaries: ', 3 );