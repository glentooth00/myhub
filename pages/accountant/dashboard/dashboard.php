<?php /* Accountant Model - Dashboard SPA - Main Controller */

use App\Services\AppView;


// ----------
// -- AUTH --
// ----------

allow( 'accountant' );




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
$app->view->with( 'title', 'Accountant' );
include $app->view->getFile();