<?php /* Admin Tools - Show Process Clients Page Controller */

use App\Models\Task as TaskModel;
use App\Models\Client as ClientModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\ClientStatement as ClientStatementModel;


// FIA TCCs are sorted by issue date, so the oldest TCCs are used first.
// Trades are also sorted by date, so the oldest trades are covered first.
// TCCs are only valid for 1 year from date of issue.
// TCCs can only cover trades that happened BEFORE the TCC expires.
// TCCs can only cover trades that happened AFTER the TCC was issued.
// There are two types of cover. FIA and SDA.
// Max available SDA per year is 1000000, but can be lower if the client provides a lower SDA mandate.
// Max available FIA per year is 10000000, but can be lower if the client provides a lower FIA mandate.
// SDA cover do not require TCCs or approval (except for the SDA mandate).
// SDA available = min(1000000, client->sda_mandate) - client->sda_used.
// FIA remaining = min(10000000, client->fia_mandate) - client->fia_used.
// FIA available = client->fia_approved (sum of all available tcc pin amounts) - client->fia_used.
// SDA allowance should be allocated first.
// Trades should be tagged as "SDA", "FIA" or "SDA/FIA" via the trade->sda_fia field.
// TCC Allocations schema: [ trade_id => amount_allocated_to_trade, ... ]
// TCC Allocations Example: allocated_trades = json_encode( [ 'CH1234' => 150000, '345631' => 250000, ... ] )
// Trade Allocations schema: [ tcc_pin => amount_covered_by_pin, ... ]
// Trade Allocations Example: allocated_pins = json_encode( [ '_SDA_' => 180000, 'XDR23FD9' => 320000, ... ] )


// CREATE TABLE `trades` (
//   `id` int(11) NOT NULL AUTO_INCREMENT,
//   `trade_id` varchar(20) DEFAULT NULL,
//   `date` date DEFAULT NULL,
//   `forex` enum('Capitec','Mercantile') DEFAULT NULL,
//   `forex_reference` varchar(20) DEFAULT NULL COMMENT 'e.g. Mercantile Deal Ref',
//   `otc` enum('OVEX','VALR') DEFAULT NULL,
//   `otc_reference` varchar(20) DEFAULT NULL,
//   `client_id` varchar(20) DEFAULT NULL,
//   `sda_fia` varchar(10) DEFAULT NULL,
//   `zar_sent` decimal(15,2) DEFAULT NULL,
//   `usd_bought` decimal(15,2) DEFAULT NULL,
//   `trade_fee` decimal(5,2) DEFAULT NULL,
//   `forex_rate` decimal(15,3) DEFAULT NULL,
//   `zar_profit` decimal(15,2) DEFAULT NULL,
//   `percent_return` decimal(5,2) DEFAULT NULL,
//   `fee_category_percent_profit` decimal(5,2) DEFAULT NULL,
//   `recon_id1` varchar(20) DEFAULT NULL,
//   `recon_id2` varchar(20) DEFAULT NULL,
//   `amount_covered` decimal(15,2) DEFAULT NULL,
//   `amount_remaining` decimal(15,2) DEFAULT NULL,
//   `allocated_pins` text,
//   PRIMARY KEY (`id`),
//   UNIQUE KEY `trade_id` (`trade_id`)
// )

// CREATE TABLE `tccs` (
//   `id` int(11) NOT NULL AUTO_INCREMENT,
//   `tcc_id` varchar(50) NOT NULL,
//   `client_id` varchar(20) NOT NULL,
//   `status` enum('Pending','Awaiting Docs','Approved','Declined','Expired') NOT NULL DEFAULT 'Pending',
//   `application_date` date DEFAULT NULL,
//   `date` date DEFAULT NULL,
//   `amount_cleared` decimal(15,2) DEFAULT NULL,
//   `rollover` decimal(15,2) DEFAULT NULL,
//   `amount_reserved` decimal(15,2) DEFAULT NULL,
//   `amount_cleared_net` decimal(15,2) DEFAULT NULL,
//   `amount_used` decimal(15,2) DEFAULT NULL,
//   `amount_remaining` decimal(15,2) DEFAULT NULL,
//   `amount_available` decimal(15,2) DEFAULT NULL,
//   `expired` int(11) DEFAULT NULL,
//   `tcc_pin` varchar(20) DEFAULT NULL,
//   `notes` varchar(255) DEFAULT NULL,
//   `allocated_trades` varchar(255) DEFAULT NULL,
//   `tax_case_no` varchar(20) DEFAULT NULL,
//   `tax_cert_pdf` varchar(255) DEFAULT NULL,
//   `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
//   `created_by` varchar(20) NOT NULL DEFAULT '_system_',
//   `updated_at` datetime DEFAULT NULL,
//   `updated_by` varchar(20) DEFAULT NULL,
//   `sync_at` datetime DEFAULT NULL,
//   `sync_by` varchar(20) DEFAULT NULL,
//   `sync_from` enum('local','remote') DEFAULT NULL,
//   `sync_type` enum('new','update') DEFAULT NULL,
//   PRIMARY KEY (`id`),
//   UNIQUE KEY `tcc_id` (`tcc_id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

// -------------
// -- REQUEST --
// -------------

$action = $_POST['action'] ?? 'none';
$jobId = $_POST['jobId'] ?? null;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    /* Fetch Job Progress while updating the clients db table */

    if ( $action === 'fetchProgress' )
    {
      $taskQueue = new TaskModel( $app );
      $task = $taskQueue->getLastTaskByJobId( $jobId );
      if ( ! isset( $task ) ) { $task = new stdClass(); $task->progress = 0; }
      else { $task->progress = round( $task->progress ); }
      debug_log( "fetchProgress(), Job Id: $jobId, progress: {$task->progress}%" );
      json_response( [ 'success' => true,  'data' => $task ] );
    } // end: fetchProgress


    if ( $action === 'processClients' )
    {
      debug_clean_logs();

      debug_log( $_POST, 'IS POST Request - POST: ', 2 );

      $progress = 0;
      $year = date('Y');
      $now = date('Y-m-d H:i:s');

      $taskQueue = new TaskModel( $app );
      $taskOptions = [ 'jobId' => $jobId, 'status' => 'running', 'time' => $now, 'expiresAfter' => 15 ]; // 15 minutes
      $task = $taskQueue->submitTask( 'Process clients.', $taskOptions );

      $chTradingFee = $app->config->get( 'trading', 'fee', 60 );     

      $clientModel = new ClientModel( $app );
      $clients = $app->db->table( 'clients' )->getAll();

      $statusModel = new ClientStateModel( [ 'redoAllocations' => true, 'resetFrom' => '2023-08-07' ] );

      $updateEvery = 200; // clients
      $remainingProgress = 100 - $progress;
      $numberOfUpdates = floor( count( $clients ) / $updateEvery );
      $progressIncrement = $numberOfUpdates
        ? floor( $remainingProgress / $numberOfUpdates )
        : $remainingProgress;

      foreach ( $clients as $index => $client ) {

        if ( $client->client_id == '03a1019c' ) {

          debug_log(PHP_EOL);
          debug_log( '***************************************************' );
          debug_log( "Processing: $client->name [$client->client_id]" );
          debug_log( '***************************************************' );


          /* Client Statement */

          // Only trades executed in the current year.
          $clientTrades = $clientModel->getClientTrades( $client, ['type' => 'All', 'year' => $year] );
          $statement = new ClientStatementModel( $client, $clientTrades, $chTradingFee );        

          debug_log( $statement->initialCapital, 'Initial Capital: ' );
          debug_log( $statement->fiaMandateRemaining, 'FIA Mandate Remaining: ' );
          debug_log( $statement->sdaMandateRemaining, 'SDA Mandate Remaining: ' );

          debug_log( $statement->toCsv(), 'Statement CSV: ' );

          debug_log( $statement->totalNetProfit, 'Total Net Profit: ' );
          debug_log( round( $statement->totalNetReturn, 2 ), 'Total Net Return: ' );


          /* Client Tax Clearance Cover Allocations */

          // $onlyApproved = ! $statusModel->redoAllocations;
          // Only client TCCs that have valid pins and are / were expendable in the current year.
          $clientTccPins = $clientModel->getClientTccPins( $client, 'Approved', [ 'year' => $year ] );

          debug_log( PHP_EOL );
          debug_log( count( $clientTrades ), 'Client Trades Count: ' );
          debug_log( count( $clientTccPins ), 'Client TCCs Count: ' );

          $tradesThatNeedCoverAll = $statusModel->getTradesThatNeedCover( $statement->trades );
          $tccsWithAvailabeCover = $statusModel->getTccsWithAvailabeCover( $clientTccPins );
          $sdaCoverAvailable = $statusModel->getClientSdaCoverAvailable( $client );

          debug_log( PHP_EOL );
          debug_log( count( $tradesThatNeedCoverAll ), 'Trades That Need Cover: ' );
          debug_log( count( $tccsWithAvailabeCover ), 'TCCs With Availabe Cover: ' );

          debug_log( PHP_EOL );
          debug_log( 'Allocate SDA (Standard Foreign Investment Allowance: Max 1Mil) cover first:' );
          debug_log( '---' );
          debug_log( $client->sda_mandate, 'Client SDA Mandate: ' );
          debug_log( $sdaCoverAvailable, 'SDA Cover Available: ' );
          debug_log( '---' );

          // Create a working clone of the trades that need cover.
          $tradesThatNeedCover = $tradesThatNeedCoverAll;

          foreach ( $tradesThatNeedCover as $trade ) {

            debug_log( 'Processing trade_'  . $trade->trade_id . ' (SDA Round), ' . floor( $trade->zar_sent ) .
              ', ' . $trade->date . ', needs ' . $trade->cover_required . ' cover, ' . 
              $sdaCoverAvailable . ' available, pins = ' . $trade->allocated_pins );

            if ( $sdaCoverAvailable <= 0 ) break; // No more SDA cover available

            // Get the trade's existing allocations
            $allocatedPins = $trade->allocated_pins ? json_decode( $trade->allocated_pins, true ) : [];

            // If it has _SDA_ cover, ammend the _SDA_ amount.
            $coverToAllocate = min( $sdaCoverAvailable, $trade->cover_required );
            if ( isset( $allocatedPins['_SDA_'] ) ) $allocatedPins['_SDA_'] += $coverToAllocate;
            else $allocatedPins['_SDA_'] = $coverToAllocate;

            // Update the trade's allocations
            $trade->allocated_pins = json_encode( $allocatedPins );

            // Update the amount covered for this trade
            $trade->amount_covered += $coverToAllocate;
            $trade->cover_required -= $coverToAllocate;

            // If there are more allocations, we know this is going to be a mixed type trade.
            $mixedType = count( $allocatedPins ) > 1;
            $trade->sda_fia = $mixedType ? 'SDA/FIA' : 'SDA';

            // Tag the trade as updated
            $trade->updated = true;

            // Update the amount used for this client
            $client->sda_used += $coverToAllocate;

            // If coverToAllocate >= sdaCoverAvailable, set sdaCoverAvailable, break out of loop.
            $sdaCoverAvailable -= $coverToAllocate;
            if ( $sdaCoverAvailable <= 0 ) break;

          } // END: foreach ( $tradesThatNeedCover as $trade ) - SDA Round


          // Remove fully covered trades from the tradesThatNeedCover array.
          $tradesThatNeedCover = array_filter( $tradesThatNeedCover, function( $trade ) {
            $fullyCovered = $trade->zar_sent - $trade->amount_covered <= 0;
            return !$fullyCovered;
          } );


          debug_log( PHP_EOL );
          debug_log( 'Allocate FIA (Special Extended Foreign Investment Allowance: Max 10Mil) cover next:' );
          debug_log( '---' );
          debug_log( $client->fia_mandate, 'Client FIA Mandate: ' );
          debug_log( '---' );
  
          foreach ( $tccsWithAvailabeCover as $tcc ) {

            debug_log( PHP_EOL );
            debug_log( 'Allocate ' . $tcc->tcc_pin . ', ' . $tcc->date . ', val: ' . floor( $tcc->amount_cleared ) .
              ', net: ' . floor( $tcc->amount_cleared_net ) . ', used: ' . floor( $tcc->amount_used ) . 
              ', ununsed: ' . floor( $tcc->amount_remaining ) . ', avail: ' . floor( $tcc->amount_available ) . 
              ', trades = ' . $tcc->allocated_trades );
            debug_log( '<' );

            // Get the TCC's existing allocations
            $allocatedTrades = $tcc->allocated_trades ? json_decode( $tcc->allocated_trades, true ) : [];

            // See if we can cover any needy trades with this TCC
            foreach ( $tradesThatNeedCover as $trade ) {

              if ( $tcc->amount_available <= 0 ) break; // No more cover available

              debug_log( 'Cover trade_'  . $trade->trade_id . ', ' . floor( $trade->zar_sent ) .
                ', ' . $trade->date . ', needs ' . $trade->cover_required . 
                ' cover, PIN ' . $tcc->tcc_pin . ' has ' . floor( $tcc->amount_available ) . ' available, pins = ' . 
                $trade->allocated_pins );

              if ( ! $statusModel->tccCanCoverTrade( $tcc, $trade ) ) continue;

              // Get the trade's existing allocations
              $allocatedPins = $trade->allocated_pins ? json_decode( $trade->allocated_pins, true ) : [];

            
              // Update the TCC

              // If the trade is already allocated to this TCC, update the amount allocated.
              $coverToAllocate = min( $tcc->amount_available, $trade->cover_required );
              // debug_log( 'TCC cover to allocate: ' . $coverToAllocate );

              // WARNING: The "+=" in the  following code might have unforseen consequences...?
              if ( isset( $allocatedTrades[ $trade->trade_id ] ) ) $allocatedTrades[ $trade->trade_id ] += $coverToAllocate;
              else $allocatedTrades[ $trade->trade_id ] = $coverToAllocate;

             // If one of the allocations is _SDA_, we know this going to be a mixed type TCC.
              $mixedType = isset( $allocatedPins['_SDA_'] );
              $trade->sda_fia = $mixedType ? 'SDA/FIA' : 'FIA';

              // Update the amounts for this TCC
              $tcc->amount_used += $coverToAllocate;
              $tcc->amount_remaining -= $coverToAllocate;
              $tcc->amount_available -= $coverToAllocate;

              // Mark the TCC as updated
              $tcc->updated = true;


              // Update the Trade

              // If the TCC PIN is already allocated to this trade, update the amount allocated.
              // WARNING: The "+=" in the  following code might have unforseen consequences...?
              if ( isset( $allocatedPins[ $tcc->tcc_pin ] ) ) $allocatedPins[ $tcc->tcc_pin ] += $coverToAllocate;
              else $allocatedPins[ $tcc->tcc_pin ] = $coverToAllocate;

              // Update the trade's allocations
              $trade->allocated_pins = json_encode( $allocatedPins );

              // Update the amount covered for this trade
              $trade->amount_covered += $coverToAllocate;
              $trade->cover_required -= $coverToAllocate;

              // Tag the trade as updated
              $trade->updated = true;

            } // END: foreach ( $tradesThatNeedCover as $trade )

            // Update the TCC's allocations
            $tcc->allocated_trades = json_encode( $allocatedTrades );

            debug_log( '>' );
            debug_log( 'Done allocating ' . $tcc->tcc_pin . ', used: ' . floor( $tcc->amount_used ) .
                ', unused: ' . floor( $tcc->amount_remaining ) . ', avail: ' . floor( $tcc->amount_available ) .
                ', trades = ' . $tcc->allocated_trades );

            // Remove fully covered trades from the tradesThatNeedCover array.
            $tradesThatNeedCover = array_filter( $tradesThatNeedCover, function( $trade ) {
              $fullyCovered = $trade->zar_sent - $trade->amount_covered <= 0;
              return !$fullyCovered;
            } );            

          } // END: foreach ( $tccsWithAvailabeCover as $tcc )

          debug_log( '---' );
          // Count the remaining trades with updates...
          debug_log( 'Save all updated trades: ' . 
            array_reduce( $tradesThatNeedCoverAll, function( $carry, $trade ) {
              return $carry + ( $trade->updated ? 1 : 0 );
            }, 0 )
          );
          debug_log( '---' );

          $saveOptions = [ 'autoStamp' => true, 'user' => $app->user->user_id ];

          // Cycle through ALL the trades that needed cover, and save any that were marked as updated.
          foreach ( $tradesThatNeedCoverAll as $trade ) {
            if ( $trade->updated ) {
              debug_log( 'Save trade_' . $trade->trade_id . ', value: ' . floor( $trade->zar_sent ) . 
                ', covered: ' . $trade->amount_covered . ', needs: ' . $trade->cover_required .
                ', pins = ' . $trade->allocated_pins );
              $result = $app->db->table( 'trades' )->save( (array) $trade, $saveOptions );              
            }
          }

          debug_log( '---' );
          // Count the tccs with updates...
          debug_log( 'Save all updated TCCs: ' . array_reduce( $tccsWithAvailabeCover, function( $carry, $tcc ) {
            return $carry + ( $tcc->updated ? 1 : 0 );
          } ) );
          
          debug_log( '---' );

          // Cycle through the TCCs that were updated and save them.
          // NOTE: Some TCCs are "auto expired" and marked as updated when we run $statusModel->getTccsWithAvailabeCover().
          foreach ( $tccsWithAvailabeCover as $tcc ) {
            if ( $tcc->updated ) {
              debug_log( 'Save updated PIN ' . $tcc->tcc_pin . ', ' . $tcc->date . ', ' . $tcc->status . 
                ', ' . floor( $tcc->amount_cleared ) . ', net: ' . floor( $tcc->amount_cleared_net ) . 
                ', used: ' . $tcc->amount_used . ', unused: ' . $tcc->amount_remaining . 
                ', avail: ' . $tcc->amount_available . ', trades = ' . $tcc->allocated_trades );
              $result = $app->db->table( 'tccs' )->save( (array) $tcc, $saveOptions ); 
            }
          }


          /* Update Client Stats */

          $totalFiaPending = 0;
          $clientPendingTCCs = $clientModel->getPendingTccs( $client->client_id );
          foreach ( $clientPendingTCCs as $tcc ) { $totalFiaPending += $tcc->amount_cleared_net; }

          $totalFiaDeclined = 0;
          $clientDeclinedTCCs = $clientModel->getDeclinedTccs( $client->client_id );
          foreach ( $clientDeclinedTCCs as $tcc ) { $totalFiaDeclined += $tcc->amount_cleared_net; }

          $totalFiaApproved = 0;
          foreach ( $clientTccPins as $tcc ) {
            $totalFiaApproved += $tcc->status == 'Expired' ? $tcc->amount_used : $tcc->amount_cleared_net;
          }

          $clientStats = [
            'id' => $client->id,
            'fia_approved' => $totalFiaApproved,
            'sda_used' => $statement->totalSDA,
            'fia_used' => $statement->totalFIA,
            'fia_pending' => $totalFiaPending,
            'fia_declined' => $totalFiaDeclined,
          ];

          debug_log( $clientStats, 'Save client stats: ' );

          // Save client stats
          $app->db->table( 'clients' )->update( $clientStats, $saveOptions ); 

        }

        // Update progress every $updateEvery clients
        if ( $index % $updateEvery === 0 && $index !== 0 ) {
          session_write_close();
          $progress += $progressIncrement;
          $progress = min( 100, $progress ); // Just in case :)
          $progressMessage = "Processing client: $index";
          debug_log( "$progressMessage, progress: $progress%" );
          $taskQueue->updateProgress( $app->db, $app->user, $task, $progress, $progressMessage );
          usleep( 10000 ); // 10ms
          session_start();
        }

        if ( $index > 10000 ) break; // safety net

        // usleep( 1000 ); // 1ms
      }

      $clientsCount = count( $clients );
    	$message = "Processing $clientsCount clients completed successfully.";
      $taskQueue->recordTaskCompletion( $app->db, $app->user, $task, $message );
      $taskQueue->deleteExpiredTasks( $app->db );
      json_response( ['success' => true, 'message' => $message] );

    } // END: processClients()

    throw new Exception( 'Invalid or missing request action.' );

  }

  catch ( Exception $ex ) {
    $app->logger->log( $ex->getMessage(), 'error' );
    $file = $ex->getFile();
    $line = $ex->getLine();
    $message = $ex->getMessage();
    $message .= "<br>---<br>Error on line: $line of $file";
    json_response( [ 'success' => false, 'message' => $message ] );
  }

}



// ---------
// -- GET --
// ---------    