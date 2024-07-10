<?php /* Client Module - Statement Controller */

global $app;


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





// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) respond_with( 'Bad request', 400 );





// ---------
// -- GET --
// ---------

function getPDFFileName( $statement ) {
  $client = $statement->getData( 'client' );
  $clientNameSlug = urlencode( str_replace( ' ', '_', $client->name ) );
  return 'Currency_Hub_Statement_' . $clientNameSlug . '_' . date( 'YmdHis' ) . '.pdf';
}


use_database();
$statement = new ClientStatementModel( $app );
$statement->generate( $clientUid, $year ? [ 'year' => $year ] : [] );

if ( $app->user->role == 'accountant' )
{
  $user = $app->db->getFirst( 'users', $app->user->id );
  debug_log( $user, 'User is accountant: ', 3 );
  $client = $statement->getData( 'client' );
  debug_log( $client, 'Check if this is the user\'s client: ', 3 );
  // Test if user->first_name appears in client->accountant
  if ( strpos( $client->accountant, $user->first_name ) === false )
  {
    respond_with( 'Unauthorized: Client not linked to your account.', 403 );
  }
}


include $app->pagesDir . __DS__ . '_templates' . __DS__ . 'default-theme' . 
  __DS__ . 'client' . __DS__ . 'stmtPDF.php';


global $pdf;
$pdf->Output( getPDFFileName( $statement ), 'I' );