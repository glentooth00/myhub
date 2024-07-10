<?php /* Accountant Module - TCCs SPA - Main Controller */

global $app;

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'accountant' );



// -------------
// -- REQUEST --
// -------------

$app->viewPartial = $_GET['view'] ?? 'list';

if ( ! in_array($app->viewPartial, ['list', 'details', 'edit'] ) )
  redirect( '404' );



// -------------
// -- CONTROL --
// -------------

$app->subControllerFile = __DIR__ . __DS__ . $app->partialsRef .
  __DS__ . $app->viewPartial . '.php';

require $app->subControllerFile;



// ----------
// -- VIEW --
// ----------

$app->view = new AppView( $app, ['variant' => $app->viewPartial] );
$app->view->with( 'title', 'AIT TAX Clearances' );

include $app->view->getFile();
