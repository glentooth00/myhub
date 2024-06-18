<?php /* Client Module - Statement Controller */

use App\Models\ClientStatement as ClientStatementModel;



// ----------
// -- AUTH --
// ----------

allow( 'client, admin, accountant' );



// -------------
// -- REQUEST --
// -------------

$clientUid = $_GET['uid'] ?? null;
$year = $_GET['year'] ?? null;


if ( ! $clientUid ) respond_with( 'Bad request', 400 );


$db = use_database();

$statement = new ClientStatementModel( $app );
$statement->generate( $clientUid, [ 'year' => $year ] );

$year = $statement->getData( 'year' );
$lines = $statement->getData( 'lines' );
$client = $statement->getData( 'client' );


if ( $app->user->role == 'accountant' )
{
  $user = $db->getFirst( 'users', $app->user->id );
  debug_log( $user, 'User is accountant: ', 3 );
  debug_log( $client, 'Check if this is their client: ', 3 );
  // Test if user->first_name appears in client->accountant
  if ( strpos( $client->accountant, $user->first_name ) === false )
  {
    respond_with( 'Unauthorized: Client not linked to your account.', 403 );
  }
}


include $app->pagesDir . __DS__ . '_templates' . __DS__ . 'default-theme' . 
  __DS__ . 'client' . __DS__ . 'stmtPDF.php';


$pdf->Output( 'Currency_Hub_Statement_' . urlencode(str_replace( ' ', '_', $client->name )) . '_' . date( 'YmdHis' ) . '.pdf', 'I' );