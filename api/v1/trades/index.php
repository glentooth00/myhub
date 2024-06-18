<?php /* API Endpoint Controller: "api/v1/trades" */

require __DIR__ . '/../api.php';


use App\Models\Tcc as TccModel;
use App\Models\Trade as TradeModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\TradeS2Mapper as TradeS2Mapper;


// Remember, we log the requester IP and Agent in "api.php",
// so we do not have to do it here.
debug_log( 'API endpoint: "api/v1/trades" says hi!  ' .
  'Request type = ' . $_SERVER['REQUEST_METHOD'], '', 2 );




// ---------
// -- CLI --
// ---------

if ( $app->request->cli ) respond_with( 'Bad request', 400 );




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  function array_find( $array, $key, $value )
  {
    if ( ! $array ) return null;
    foreach( $array as $item ) {
      if ( $item[$key] == $value ) return $item;
    }
    return null;
  }

  function getTradeUIDs( $tradeRows )
  {
    $tradeUIDs = [];
    foreach( $tradeRows as $tradeRow ) { $tradeUIDs[] = $tradeRow[0]; }
    return $tradeUIDs;
  }

  function toSqlDateFormat( $dateString )
  {
    // Parses either 'Y-m-d', 'd/m/Y' or '2024-03-06T22:00:00.000Z'
    if ( ! $dateString ) return null;
    if ( strpos( $dateString, '/' ) !== false ) {
      $pos = strpos( $dateString, ' ' );
      if ( $pos ) $dateString = substr( $dateString, 0, $pos );
      $dateTime = DateTime::createFromFormat( 'd/m/Y' , $dateString );
      if ( $dateTime !== false ) return $dateTime->format( 'Y-m-d' );
    }
    $timestamp = strtotime( $dateString );
    if ( $timestamp === false ) return null;
    return date( 'Y-m-d', $timestamp );
  }


  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );
    
  $jsonData = file_get_contents( 'php://input' );
  $_POST = json_decode( $jsonData, true ); 
  
  debug_log( $_POST, 'API POST Request. Data = ', 3 );

  $action = $_POST['action'] ?? null;
  debug_log( $action, 'POST Action: ', 2 );


  if ( $action === 'submitTrades' )
  {
    debug_log( 'OK, so we are submitting client trades!', '', 2 );

    use_database();
    debug_log( 'Database connected.', '', 3 );
    
    $clientsData = $_POST['clientsData'] ?? [];

    $response = [];

    foreach( $clientsData as $clientData )
    {
      $app->db->pdo->beginTransaction();
      debug_log( 'Submit Trades: Begin Transaction', '', 2 );

      try {

        $clientUid = $clientData['id'] ?? 'noid';
        $clientName = $clientData['name'] ?? 'noname';
        $tradeColumns = $clientData['tradeColumns'] ?? [];
        $tradeRows = $clientData['trades'] ?? [];
        $tradesCount = count( $tradeRows );

        $tradeModel = new TradeModel( $app );
        debug_log( 'TradeModel instantiated.', '', 3 );
        debug_log( $tradesCount, 'Trades Count: ', 2 );


        /* Reset related TCC allocations if necessary */

        $tradeUIDs = getTradeUIDs( $tradeRows );
        debug_log( $tradeUIDs, 'Trade UIDs: ', 2 );

        $existingTrades = $tradeModel->getTradesByUid( $tradeUIDs );
        debug_log( $existingTrades, 'Existing Trades: ', 2 );

        $tccModel = new TccModel( $app );
        if ( $existingTrades ) {
          debug_log( 'Reset Related Cover: Transaction started.', '', 2 );
          foreach( $existingTrades as $tradeToReset ) {
            $tradeRow = array_find( $tradeRows, 0, $tradeToReset->trade_id );
            debug_log( $tradeRow, 'Trade Row: ', 2 );
            $newTradeType = $tradeRow[4];
            $newTradeDate = toSqlDateFormat( $tradeRow[2] );
            debug_log( compact( 'newTradeType', 'newTradeDate' ), '', 2 );
            // Reset related TCC allocations if the trade type or date has changed
            if ( $newTradeType != $tradeToReset->sda_fia or $newTradeDate != $tradeToReset->date ) {
              $pins = $tradeToReset->allocated_pins ? json_decode( $tradeToReset->allocated_pins, true ) : null;
              if ( ! $pins ) $pins = [];
              unset( $pins['_SDA_'] ); // Remove _SDA_ so we don't loop over SDA pins.
              debug_log( $pins, 'Pins to remove this trade from: ', 2 );
              foreach( $pins as $pin => $coverAmount ) {
                $tccModel->removeTradeFromAllocs( $pin, $tradeToReset );
              }
            }
          }
          debug_log( 'Reset Related Cover: Transaction committed.', '', 2 );
        }

        /* Save Trades */

        $updated = 0;
        $inserted = 0;

        $tradeS2Mapper = new TradeS2Mapper();

        foreach( $tradeRows as $index => $tradeRow )
        {
          // debug_log( $tradeRow, 'Trade Row: ', 3 );

          array_pop( $tradeRow ); // Posted
          array_pop( $tradeRow ); // Client Name

          $tradeData = $tradeS2Mapper->mapTradeRowValues( $tradeRow );
          debug_log( $tradeData, 'Trade Data: ', 3 );

          $saveResult = $app->db->table( 'trades' )->upsert( $tradeData, 'trade_id' );
          if ( ! $saveResult ) throw new Exception( "Failed to save trade $index for client $clientUid." .
              ' Result = ' . json_encode( $saveResult ) );

          if ( $saveResult['status'] === 'updated' ) $updated++;
          else if ( $saveResult['status'] === 'inserted' ) $inserted++;
        }

        $submitResult = compact( 'tradesCount', 'inserted', 'updated' );
        debug_log( $submitResult, 'Submit Trades Result: ', 2 );


        /* Update the client's SDA/FIA allowance allocations & stats */

        $clientStateModel = new ClientStateModel( $app );
        $updateResult = $clientStateModel->updateStateFor( $clientUid );
        $statement = $updateResult['statement'];
        $client = $updateResult['client'];


        /* Prepare and send the response data */

        // Add statement info the response data as base
        // e.g. sda_used, fia_used, sda_mandate_remaining, fia_mandate_remaining, ...
        $respData = $statement->toS2Data();

        // Add more details to the response data
        $respData->clientUid = $clientUid;
        $respData->name = $clientData['name'];
        $respData->stateBefore = $clientData['stateBefore'];
        $respData->result = $submitResult;
        $respData->fiaAvailable = $client['fia_available'];
        $respData->fiaApproved = $client['fia_approved'];
        $respData->fiaDeclined = $client['fia_declined'];
        $respData->fiaPending = $client['fia_pending'];
        $respData->fiaUnused = $client['fia_unused'];
        $respData->userUid = $client['updated_by'];
        
        $app->db->pdo->commit();
        debug_log( 'Submit Trades: Commit Transaction', '', 2 );

        debug_log( $respData, 'Response data: ', 2 );      

        $response[] = $respData;

      } // try: submit client trades

      catch ( Exception $ex ) {
        $app->db->safeRollback();
        $error = $ex->getMessage();
        $respData = new stdClass();
        $respData->clientUid = $clientUid;
        $app->logger->log( $error, 'error' );
        $respData->error = $error;
        $response[] = $respData;
      }        

    } // end: foreach( $clientsData as $clientData )

    $clientCount = count( $response );
    $failedCount = count( array_filter( $response, function( $item ) { return isset( $item->error ); } ) );

    $message = "Post to S3 complete. Submitted $clientCount clients. $failedCount failed.";

    json_response( [
      'success' => true,
      'message' => $message,
      'clients' => $clientCount,
      'errors' => $failedCount,
      'data' => $response
    ] );

  } // end: submitTrades


  json_response( [ 'success' => false, 'message' => 'Invalid or missing action.' ] );

}




// ---------
// -- GET --
// ---------

// e.g. api/v1/trades?do=fetch&fs=1&day=1&timestamp=1234567890


// if ( ! $app->request->isRPC ) respond_with( 'Bad request', 400 );
if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

debug_log( $_GET, 'API GET Request. Params = ', 3 );


// Request

$do = $_GET['do'] ?? null;
$days = $_GET['days'] ?? null;
$day = $_GET['d'] ?? null;
$week = $_GET['w'] ?? null;
$month = $_GET['m'] ?? null;
$months = $_GET['months'] ?? null;
$year = $_GET['y'] ?? null;
$fieldset = $_GET['fs'] ?? null;
$offset = $_GET['offset'] ?? null;
$limit = $_GET['limit'] ?? null;


try {

  if ( $do != 'count' and $do != 'fetch' ) throw new Error( 'Invalid Request' );

  
  use_database();
  debug_log( 'Database connected.', '', 3 );


  /* query */
  $q = $app->db->table( 'trades' );

  // $day = 1 means today.
  // $day = 2 means yesterday.
  if ( $day ) {
    $day = (int) $day - 1;
    $q->where( 'date', '=', date( 'Y-m-d', strtotime( "-$day days" ) ) );
  }

  if ( $days ) $q->where( 'date', '>=', date( 'Y-m-d', strtotime( "-$days days" ) ) );

  // Get the trades for a week, where `$week` is the week number relative to the current week.
  // i.e. $week = 2 means: Get the trades for the week, two weeks ago.
  if ( $week ) {
    $week = (int) $week;
    $q->where( 'date', '>=', date( 'Y-m-d', strtotime( "monday this week -$week weeks" ) ) );
    $q->where( 'date', '<', date( 'Y-m-d', strtotime( "monday this week -$week weeks +1 week" ) ) );
  }

  if ( $month ) {
    $month = (int) $month;
    $year = $year ? (int) $year : date( 'Y' );
    $q->where( 'date', '>=', "$year-$month-01" );
    $q->where( 'date', '<', date( 'Y-m-d', strtotime( "$year-$month-01 +1 month" ) ) );
  }

  // Get `months` number of months back from the current month, including the current month.
  if ( $months ) {
    $months = (int) $months;
    $months--; // Subtract 1 to include the current month.
    $year = $year ? (int) $year : date( 'Y' );
    $q->where( 'date', '>=', date( 'Y-m-d', strtotime( "first day of -$months months" ) ) );
    $q->where( 'date', '<', date( 'Y-m-d', strtotime( "first day of +1 month" ) ) );
  }

  if ( $year and ! $month ) {
    $year = (int) $year;
    $q->where( 'date', '>=', "$year-01-01" );
    $q->where( 'date', '<', date( 'Y-m-d', strtotime( "$year-01-01 +1 year" ) ) );
  }


  if ( $do == 'count' ) {

    echo $q->count();

  } // count


  if ( $do == 'fetch' ) {

    switch ( $fieldset )
    {
      // CONCAT("\'", client_id) adds a ' in-front of the id to ensure Google sees
      // it as a string. We need this to make LOOKUP functions work correctly!
      case 1: $select = 'CONCAT("\'", trade_id) AS trade_id, ' .
        'otc, date, CONCAT("\'", client_id) AS client_id, ' .
        'sda_fia, zar_sent, usd_bought, trade_fee, forex_rate, ' .
        'zar_profit, percent_return, fee_category_percent_profit, ' .
        'amount_covered, allocated_pins, created_at, created_by, ' .
        'updated_at, updated_by, deleted_at, deleted_by';
        break;    
      default: $select = null;
    }

    if ( ! $select ) throw new Error( 'Invalid Request' );

    $q->select( $select );

    if ( $limit ) $q->limit( "$offset, $limit" );

    $trades = $q->getAll();

    if ( ! $trades ) return;

    // echo csv headers
    echo implode( ';', array_keys( (array) $trades[0] ) ), PHP_EOL;

    // echo csv rows
    foreach( $trades as $trade )
    {
      echo implode( ';', (array) $trade ), PHP_EOL;
    }

  } // fetch


} // try

catch ( Error $e ) {
  $app->logger->log( $e->getMessage(), 'error' );
  echo 'Oops, something went wrong!';
}

catch ( Exception $e ) {
  $app->logger->log( $e->getMessage(), 'exception' );
  echo 'Oops, something went wrong!';
}


// `id` int(11) NOT NULL AUTO_INCREMENT,
// `trade_id` varchar(20) DEFAULT NULL,
// `date` date DEFAULT NULL,
// `forex` enum('Capitec','Investec','Mercantile') DEFAULT NULL,
// `forex_reference` varchar(20) DEFAULT NULL,
// `otc` enum('OVEX','VALR') DEFAULT NULL,
// `otc_reference` varchar(20) DEFAULT NULL,
// `client_id` varchar(20) DEFAULT NULL,
// `sda_fia` varchar(10) DEFAULT NULL,
// `zar_sent` decimal(15,2) DEFAULT NULL,
// `usd_bought` decimal(15,2) DEFAULT NULL,
// `trade_fee` decimal(5,2) DEFAULT NULL,
// `forex_rate` decimal(6,3) DEFAULT NULL,
// `zar_profit` decimal(15,2) DEFAULT NULL,
// `percent_return` decimal(5,2) DEFAULT NULL,
// `fee_category_percent_profit` decimal(5,2) DEFAULT NULL,
// `recon_id1` varchar(20) DEFAULT NULL,
// `recon_id2` varchar(20) DEFAULT NULL,
// `amount_covered` decimal(15,2) DEFAULT NULL,
// `allocated_pins` text DEFAULT NULL,
// `created_at` datetime NOT NULL DEFAULT current_timestamp(),
// `created_by` varchar(20) NOT NULL DEFAULT '_system_',
// `updated_at` datetime DEFAULT NULL,
// `updated_by` varchar(20) DEFAULT NULL,
// `deleted_at` datetime DEFAULT NULL,
// `deleted_by` varchar(20) DEFAULT NULL,