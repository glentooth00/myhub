<?php /* API Endpoint Controller: "api/v1/tccs" */

require __DIR__ . '/../api.php';


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

$stat = $_GET['stat'] ?? null;

if ( $stat == 'count' ) {
  use_database();
  debug_log( 'Database connected.', '', 3 );
  echo $app->db->table( 'tccs' )->count();
  exit;
}

$fieldset = $_GET['fieldset'] ?? null;
$offset = $_GET['offset'] ?? null;
$limit = $_GET['limit'] ?? null;
$year = $_GET['year'] ?? date( 'Y' );


switch ( $fieldset )
{
  // CONCAT("\'", client_id) adds a ' in-front of the id to ensure Google sees it as a string.
  // We need this to make LOOKUP functions work correctly!
  case 1: $select = 'tcc_id,CONCAT("\'", client_id) AS client_id,status,application_date,date,amount_cleared,tcc_pin,amount_remaining,expired';
    break;
  default: $select = null;
}


try {

  if ( ! $select ) throw new Error( 'Invalid Request' );
	
  use_database();
  debug_log( 'Database connected.', '', 3 );

  /* query */
  $q = $app->db->table( 'tccs' )->select( $select )->where( 'YEAR(`date`)', $year );

  if ( $limit ) $q->limit( "$offset, $limit" );

  $tccs = $q->getAll();

  /* echo csv */
  foreach( $tccs as $tcc ) { echo implode( ',', (array) $tcc ), PHP_EOL; }
}

catch ( Error $e ) {
  $app->logger->log( $e->getMessage(), 'error' );
  echo 'Oops, something went wrong!';
}

catch ( Exception $e ) {
  $app->logger->log( $e->getMessage(), 'exception' );
  echo 'Oops, something went wrong!';
}