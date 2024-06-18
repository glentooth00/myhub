<?php /* Admin SPA - Tools Fetch Trades Show - Sub Controller */

use F1\HttpClient;
use F1\FileSystem;

use App\Services\GoogleAPI;

use App\Models\Client as ClientModel;



function updateJobProgress( $jobs, $jobId, $progress )
{
  $jobs[$jobId]->progress = $progress;
  $_SESSION['jobs'] = $jobs;
  session_write_close();
  usleep( 10000 ); // 10ms
  session_start();
}



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
      $jobs = $_SESSION['jobs'] ?? [];
      $noJob = new stdClass(); $noJob->progress = 0;
      $job = isset( $jobs[ $jobId ] ) ? $jobs[ $jobId ] : $noJob;
      debug_log( "fetchProgress(), Job Id: $jobId, progress: {$job->progress}%", '', 2 );

      json_response( [ 'success' => true,  'data' => $job ] );
    } // end: fetchProgress


    if ( $action === 'fetchTrades' )
    {
      debug_clean_logs();

      debug_log( PHP_EOL, '', 2 );      
      // debug_log( $app->user, 'IS POST Request - User: ', 2 );
      debug_log( $_POST, 'fetchTrades(), POST: ', 2 );

      $progress = 0;
      $now = date('Y-m-d H:i:s');
      $expiresAt = date( 'Y-m-d H:i:s', strtotime( '+1 hour' ) );

      $job = new stdClass();
      $job->desc = 'Fetch and update `trades` table from Google Sheet.';
      $job->time = $now;
      $job->expiresAt = $expiresAt;
      $job->progress = $progress;

      $savedJobs = $_SESSION['jobs'] ?? [];
      debug_log( $savedJobs, 'Saved Jobs: ', 2 );

      $jobs = [];
      // foreach ( $savedJobs as $savedJobId => $savedJob ) {
      //   if ( strtotime( $savedJob->expiresAt ) < time() ) continue;
      //   $jobs[$savedJobId] = $savedJob;
      // }

      $jobs[$jobId] = $job;
      debug_log( $jobs, 'Updated Jobs: ', 2 );  

      // Create Google API instance
      $googleApi = new GoogleAPI(
        new HttpClient(),
        __GOOGLE_PRIVATE_KEY__,
        __GOOGLE_SERVICE_EMAIL__
      );

      // Fetch spreadsheet data. NOTE: Has built in exception handling.
      $data = $googleApi->fetchSpreadsheetData( 
        __GOOGLE_DATABASE_SHEET_ID__,
        __GOOGLE_ALL_TRADES_RANGE__
      );

      $progress += 10;
      updateJobProgress( $jobs, $jobId, $progress );

      $headers = array_shift( $data['values'] );

      debug_log( $headers, 'Headers: ', 2 );

			$tradesCount = count( $data['values'] );
			debug_log( $tradesCount, 'Trades fetched: ', 2 );
			debug_log( $data['values'][0], 'First trade fetched: ', 2 );

      $app->db = use_database();

      $tradeModel = new TradeModel( $app );
      $syncBy = $app->user->user_id;

      $updateEvery = 1000; // trades
      $remainingProgress = 100 - $progress;
      $numberOfUpdates = floor( $tradesCount / $updateEvery );
      $progressIncrement = $numberOfUpdates
        ? $remainingProgress / $numberOfUpdates
        : $remainingProgress;

      $updated = 0;
      $deleted = 0;
      $inserted = 0;
      $unchanged = 0;
      $blankRows = 0;
			foreach ( $data['values'] as $index => $row )
			{
        // Skip empty rows.
        if ( empty( $row[0] ) ) { $blankRows++; continue; }

        // Map row values (no keys) to a Trade key:value array
        $tradeData = $tradeModel->mapTradeRowValues( $row );

        // Don't import this info from S2 anymore!
        unset( $tradeData['amount_covered'] );
        unset( $tradeData['allocated_pins'] );

        $upsertOptions = ['onchange' => ['date', 'client_id', 'sda_fia', 'zar_sent']];
        $result = $app->db->table( 'trades' )->upsert( $tradeData, 'trade_id', $upsertOptions );
        if ( ! $result ) throw new Exception( 'Failed to save trade. Index = ' . $index . 
        		' Trade = ' . json_encode( $tradeData ) );

        if ( $result['status'] === 'unchanged' ) $unchanged++;
        else if ( $result['status'] === 'updated' ) $updated++;
        else if ( $result['status'] === 'inserted' ) $inserted++;          

        // Update progress every $updateEvery trades
        if ( $index % $updateEvery === 0 && $index !== 0 ) {
          $progress += $progressIncrement;
          $progress = min( 100, $progress ); // Just in case :)
          debug_log( "Trade Index: $index, progress: " . round( $progress, 2 ) . '%', '', 2 );
          updateJobProgress( $jobs, $jobId, floor( $progress ) );
        }

        if ( $index > 50000 ) break; // safety net
       }

      $message = "Fetching $tradesCount trades completed successfully.<br><small><i>Stats: " .
        "Updated: $updated, Inserted: $inserted, Deleted: $deleted, Unchanged: $unchanged, " .
        "BlankRows: $blankRows<i></small>";

      debug_log( $message, 'Summary message: ', 2 );

      json_response( ['success' => true, 'message' => $message] );
    } // end: fetchTrades


    throw new Exception( 'Invalid or missing request action.' );

  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    json_response( [ 'success' => false, 'message' => $error ] );
  }

}



// ---------
// -- GET --
// ---------