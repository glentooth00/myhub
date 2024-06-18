<?php /* Admin SPA - Tools Testing Show - Sub Controller */

use F1\FileSystem;

use App\Models\Client as ClientModel;



// -------------
// -- REQUEST --
// -------------




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 3 );

    $jobId = $_POST['jobId'] ?? null;



    /** ACTION 1 **/

    if ( $action === 'test' )
    {

      sleep( 3 );

    	json_response( [ 'success' => true, 'message' => "Testing `$jobId` complete." ] );

    } // test



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    $app->db->safeRollBack();
    json_response( [ 'success' => false, 'message' => $error ] );
  }

}




// ---------
// -- GET --
// ---------

$config = use_config();
$pageTests = $config->get('tests', 'pages');
