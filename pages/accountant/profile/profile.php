<?php /* Accountant Module - Profile SPA - Main Controller */

global $app;

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'accountant' );



// -------------
// -- REQUEST --
// -------------

$app->viewPartial = $_GET['view'] ?? 'details';

if ( ! in_array( $app->viewPartial, [ 'details' ] ) )
  redirect( '404' );



// -------------
// -- CONTROL --
// -------------

$app->subControllerFile = __DIR__ . __DS__ . $app->partialsRef . 
  __DS__ . $app->viewPartial . '.php';

require $app->subControllerFile;



// ---------
// -- VIEW --
// ---------

$app->view = new AppView( $app, [ 'variant' => $app->viewPartial ] );
$app->view->with( 'title', 'My Profile' );
include $app->view->getFile();