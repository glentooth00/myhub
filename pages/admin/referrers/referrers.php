<?php /* Admin Module - Referrers SPA - Main Controller */

global $app;

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'admin' );



// -------------
// -- REQUEST --
// -------------

$app->viewPartial = $_GET['view'] ?? 'show';

if ( ! in_array( $app->viewPartial, [ 'show', 'list', 'beneficiary', 'revenue-model',
  'templates', 'referrer-type', 'model-types', 'list', 'details', 'edit', 'beneficiary-details',
  'revenue-details', 'beneficiary-edit', 'template-details', 'template-edit', 'revenue-edit', 'model-beneficiary'  ] ) ) redirect( '404' );



// -------------
// -- CONTROL --
// -------------

$app->subControllerFile = __DIR__ . __DS__ . $app->partialsRef .
  __DS__ . $app->viewPartial . '.php';

require $app->subControllerFile;



// ----------
// -- VIEW --
// ----------

$app->view = new AppView( $app, [ 'variant' => $app->viewPartial ] );
$app->view->with( 'title', 'Referrers' );

include $app->view->getFile();