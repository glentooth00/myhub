<?php /* Admin Module - Users SPA - Main Controller */

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'admin' );



// -------------
// -- REQUEST --
// -------------

$app->viewPartial = $_GET['view'] ?? 'list';

if ( ! in_array( $app->viewPartial, [ 'list', 'edit', 'profile' ] ) )
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
$app->view->with( 'title', 'Users' );
include $app->view->getFile();