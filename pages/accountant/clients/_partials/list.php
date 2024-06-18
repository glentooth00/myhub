<?php /* Accountant SPA - Clients List - Sub Controller */

use App\Models\Client as ClientModel;
use App\Models\UserSettings as UserSettingsModel;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 3 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 3 );


    /** DEFAULT ACTION **/

    throw new Exception( 'Invalid or missing request action.' );

  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    json_response( [ 'success' => false, 'message' => $error ] );
  }

} // POST




// ---------
// -- GET --
// ---------

function fullName( $client )
{
  if ( $client->first_name ) {
    $fullName = $client->first_name;
    if ( $client->middle_name ) $fullName .= ' ' . $client->middle_name;
    if ( $client->last_name ) $fullName .= ' ' . $client->last_name;
  }
  else $fullName = $client->name;

  return $fullName;
}


function clientComboID( $client )
{
  return $client->client_id . ( isset( $client->id_number ) ? " | $client->id_number" : '' );
}


function generatePDFLink( $clientUid )
{
  return 'client/statement?uid=' . $clientUid;
}


/* settings */
$settings = new UserSettingsModel( $app );


/* request */
$status = $_GET['status'] ?? $settings->getSettingValue( 'clients_status', 'Active' );
$settings->saveIfChanged( 'clients_status', $status );


/* lists */
$clientModel = new ClientModel( $app );
$clients = $clientModel->getAllByAccountant( full_name( $app->user ), [ 'status' => $status ] );