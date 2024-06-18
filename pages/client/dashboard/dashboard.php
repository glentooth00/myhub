<?php /* Client Module - Dashboard SPA - Main Controller */

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'client' );




// -------------
// -- CONTROL --
// -------------

$app->subControllerFile = __DIR__ . __DS__ . 
  $app->partialsRef . __DS__ . 'show.php';

require $app->subControllerFile;




// ----------
// -- VIEW --
// ----------

$app->view = new AppView( $app );
$app->view->with( 'title', 'Dashboard' );

include $app->view->getFile();