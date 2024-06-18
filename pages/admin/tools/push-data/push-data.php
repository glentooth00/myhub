<?php /* Admin Tools Module - Push Data SPA - Main Controller */

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'super' );



// -------------
// -- REQUEST --
// -------------

$app->viewPartial = $_GET['view'] ?? 'show';

if ( ! in_array( $app->viewPartial, [ 'show' ] ) )
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

$app->view = new AppView( $app, [ 'variant' => $app->viewPartial ] );
$app->view->with( 'title', 'Push Data' );
include $app->view->getFile();