<?php /* Accountant SPA - TCCs List - Sub Controller */

use App\Models\Tcc as TccModel;
use App\Models\UserSettings as UserSettingsModel;


// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) { exit; }




// ---------
// -- GET --
// ---------

function get_cert_url( $tcc ) {
  global $app;
  return $app->uploadsRef . '/' . $tcc->client_name . '_' . $tcc->client_id2 . '/' . 
    $tcc->tcc_pin . '.pdf?' . time();
}

function get_cert_link( $tcc ) {
  if ( empty( $tcc->tcc_pin ) or empty( $tcc->tax_cert_pdf ) ) return '';
  return '<a href="' . get_cert_url( $tcc ) . '" target="_blank" title="View Certificate PDF"' .
   ' onclick="event.stopPropagation()"><i class="fa fa-file-pdf-o"></i></a>';
}


/* settings */
$settings = new UserSettingsModel( $app );


/* request */
$category = $_GET['category'] ?? $settings->getSettingValue( 'tccs_category', 'All' );
$days = $_GET['days'] ?? $settings->getSettingValue( 'tccs_days', 'this-week' );

$settings->saveIfChanged( 'tccs_category', $category );
$settings->saveIfChanged( 'tccs_days', $days );


/* lists */
$tccModel = new TccModel( $app );
$tccs = $tccModel->getAllByAccountant( full_name($app->user), [
  'days' => $days,
  'category' => $category
] );