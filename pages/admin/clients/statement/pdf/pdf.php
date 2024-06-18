<?php /* Admin Module - Client Statement PDF Download Controller */


// ----------
// -- AUTH --
// ----------

allow( 'logged-in' );




// -------------
// -- REQUEST --
// -------------

$clientId = $_GET['id'] ?? null;
$getPrevYear = $_GET['prev'] ?? false;


if ( ! $clientId ) respond_with( 'Bad request', 400 );




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) respond_with( 'Bad request', 400 );




// ---------
// -- GET --
// ---------

function extract_Google_UUID( $googleFileUrl )
{
  $pattern = '/\/d\/([a-zA-Z0-9_-]+)\//';
  preg_match( $pattern, $googleFileUrl, $matches );
  $fileID = isset( $matches[1] ) ? $matches[1] : null;
  return $fileID;
}


try {

  $db = use_database();

  $google = use_google();

  $client = $db->getFirst( 'clients', $clientId );

  if ( ! $client ) throw new Exception( 'No client found' );

  if ( ! $client->statement_pdf ) throw new Exception( 'No PDF statement file found' );

  $googleFileUrl = $getPrevYear ? $client->spare_5 : $client->statement_pdf;

  $googleDriveUUID = extract_Google_UUID( $googleFileUrl );

  $downloadFileAs = 'Currency_Hub_Statement_' . urlencode(
    str_replace( ' ', '_', $client->name ) ) . '_' . time();

  $userEmail = 'neels@currencyhub.co.za'; // $app->security->getUsername();

  debug_log( $googleDriveUUID, 'Google Drive UUID: ', 2 );
  debug_log( $downloadFileAs, 'Download File As: ', 2 );
  debug_log( $userEmail, 'User Email: ', 2 );

  $google->downloadPdfFromDrive( $googleDriveUUID, $downloadFileAs, $userEmail );

}

catch ( Exception $e ) {
  $message = 'Failed to download PDF from Google Drive';
  if ( __DEBUG__ > 1 ) $message .= ': ' . $e->getMessage();
  debug_log( $message, '', 2 );
  // debug_log( $e, '', 3 );
  respond_with( $message );
}