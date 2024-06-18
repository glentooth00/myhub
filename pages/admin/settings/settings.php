<?php /* Admin Module - Settings SPA - Main Controller */

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'admin' );



// -------------
// -- REQUEST --
// -------------

$app->viewPartial = $_GET['view'] ?? 'show';

if ( ! in_array( $app->viewPartial, [ 'show', 'roles', 'banks', 'ncrs',
	'countries', 'provinces', 'cities', 'suburbs' ] ) ) redirect( '404' );



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
$app->view->with( 'title', 'Settings' );

include $app->view->getFile();