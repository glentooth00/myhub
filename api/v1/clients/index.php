<?php /* API Endpoint Controller: "api/v1/clients" */

require __DIR__ . '/../api.php';


// Remember, we log the requester IP and Agent in "api.php",
// so we do not have to do it here.
debug_log( 'API endpoint: "api/v1/clients" says hi!  ' .
  'Request type = ' . $_SERVER['REQUEST_METHOD'], '', 2 );




// ---------
// -- CLI --
// ---------

if ( $app->request->cli ) respond_with( 'Bad request', 400 );




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) respond_with( 'Bad request', 400 );




// ---------
// -- GET --
// ---------

// e.g. api/v1/clients?fieldset=1&timestamp=1234567890


// if ( ! $app->request->isRPC ) respond_with( 'Bad request', 400 );
if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

debug_log( $_GET, 'API GET Request. Params = ', 3 );


$stat = $_GET['stat'] ?? null;

if ( $stat == 'count' ) {
  use_database();
  debug_log( 'Database connected.', '', 3 );
  echo $app->db->table( 'clients' )->count();
  exit;
}

$fieldset = $_GET['fieldset'] ?? null;
$offset = $_GET['offset'] ?? null;
$limit = $_GET['limit'] ?? null;


$csvSeparator = ',';


switch ( $fieldset )
{
  // Trading sheet - Full update
  // CONCAT("\'", client_id) adds a ' in-front of the id to ensure Google sees it as a string.
  // `client_id` needs to be a string to make LOOKUP functions work correctly!
  case 1: $select = 'name,CONCAT("\'", client_id) AS client_id,ovex_email,' .
    'trading_capital,sda_mandate,fia_mandate,bank,accountant,' .
    'fia_approved,sda_used,fia_used,bp_number,cif_number,' .
    'fia_pending,fia_declined,ovex_ref,capitec_id,' .
    'mercantile_name,trader_id,status';
    break;    

  // Trading Sheet - Partial update
  case 2: $select = 'trading_capital,sda_mandate,fia_mandate,' .
   'bank,accountant,fia_approved,sda_used,fia_used';
   break;

  // S3 Client Proxy Table - Partial update
  case 3: $select = 'trading_capital,sda_mandate,fia_mandate,fia_approved,' .
   'sda_used,fia_used,fia_pending,fia_declined';
  $csvSeparator = ';';
  break;

  // S3 Client Proxy Table - Full update
  case 4:
    // Client ID
    // ID Number
    // Name
    // Personal Email
    // Phone Number
    // Status
    // Trading Capital
    // SDA Mandate
    // FIA Mandate
    // FIA Approved
    // SDA Used
    // FIA Used
    // FIA Pending
    // FIA Declined
    // Accountant
    // NCR
    // Bank
    // Tax Number
    // CIF Number
    // BP Number
    // Referrer UID
    // Referrer Name
    // Inhouse Referrer 15%
    // Trader ID
    // Notes
    // Created at
    // Created by
    // Deleted at
    // Deleted by
    // Updated at
    // Updated by
    $remoteColumns = [
      'CONCAT("\'", client_id) AS client_id', 'id_number', 'name', 'personal_email', 'phone_number', 'status', 'trading_capital',
      'sda_mandate', 'fia_mandate', 'fia_approved', 'sda_used', 'fia_used', 'fia_pending', 'fia_declined', 'accountant', 'ncr',
      'bank', 'tax_number', 'cif_number', 'bp_number', 'referrer_uid', 'referrer_name', 'inhouse_referrer_15_percent', 'trader_id', 'notes',
      'created_at', 'created_by', 'deleted_at', 'deleted_by', 'updated_at', 'updated_by'
    ];
    $select = implode( ',', $remoteColumns );
    $csvSeparator = ';';
    break;

  default: $select = null;
}


try {
  
  if ( ! $select ) throw new Error( 'Invalid Request' );
  
  use_database();
  debug_log( 'Database connected.', '', 3 );

  /* query */
  $q = $app->db->table( 'view_clients' )->select( $select );

  if ( $limit ) $q->limit( "$offset, $limit" );

  $clients = $q->getAll();

  /* echo csv */
  foreach( $clients as $client ) { 
    echo implode( $csvSeparator, (array) $client ), PHP_EOL;
  }
}

catch ( Error $e ) {
  $app->logger->log( $e->getMessage(), 'error' );
  echo 'Oops, something went wrong!';
}

catch ( Exception $e ) {
  $app->logger->log( $e->getMessage(), 'exception' );
  echo 'Oops, something went wrong!';
}