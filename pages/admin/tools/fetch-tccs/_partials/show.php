<?php /* Admin SPA - Tools Fetch TCCs Show - Sub Controller */

use F1\FileSystem;

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


    if ( $action === 'fetchTCCs' )
    {
      debug_clean_logs();

      debug_log( PHP_EOL, '', 2 );      
      // debug_log( $app->user, 'IS POST Request - User: ', 2 );
      debug_log( $_POST, 'fetchTCCs(), POST: ', 2 );

      $progress = 0;
      $now = date('Y-m-d H:i:s');
      $expiresAt = date( 'Y-m-d H:i:s', strtotime( '+1 hour' ) );

      $job = new stdClass();
      $job->desc = 'Fetch and update `tccs` table from Google Sheet.';
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

      // Fetch spreadsheet data. NOTE: Has built in exception handling.
      $google = use_google();
			$data = $google->fetchSpreadsheetData(
        __GOOGLE_DATABASE_SHEET_ID__,
        __GOOGLE_ALL_TCCS_RANGE__
      );

      $progress += 10;
      updateJobProgress( $jobs, $jobId, $progress );

      $headers = array_shift( $data['values'] );

      debug_log( $headers, 'Headers: ', 2 );      

			$tccsCount = count( $data['values'] );
			debug_log( $tccsCount, 'TCCs fetched: ', 2 );
			debug_log( $data['values'][0], 'First tcc fetched: ', 2 );

      $app->db = use_database();

      $tccModel = new TccModel( $app );

      $syncBy = $app->user->user_id;

      $remoteFieldNames = $tccModel->mapGoogleTableHeaders( $headers );
      debug_log( $remoteFieldNames, 'Remote data fields (mapped from headers): ', 2 );

      $localFieldNames = $tccModel->getDbColumnNames( 'Drop local only fields.' );
      debug_log( $localFieldNames, 'Local db field mames (without id and sync fields): ', 2 );

      $fields_diff = $tccModel->getFieldsMismatch( $localFieldNames, $remoteFieldNames );
      debug_log( $fields_diff, 'Fields diff: ', 2 );

      if ( count( $fields_diff ) > 0 )
      {
        json_response( [
          'success' => false,
          'message' => 'The remote data fields do not match the local database table columns.',
          'remoteFieldNames'  => $remoteFieldNames,
          'localFieldNames' => $localFieldNames,
          'fields_diff' => $fields_diff
        ] );
      }

      // Get all local TCC ids as a hash table with `tcc_id` as key and `id` as value
      $localTccIds = $tccModel->getTccIdsByUid();      

      $updateEvery = 500; // tccs
      $remainingProgress = 100 - $progress;
      $numberOfUpdates = floor( $tccsCount / $updateEvery );
      $progressIncrement = $numberOfUpdates
        ? $remainingProgress / $numberOfUpdates
        : $remainingProgress;      

      // Loop through each row of data and insert into our database
      $updated = 0;
      $deleted = 0;
      $inserted = 0;
      $unchanged = 0;
      $blankRows = 0;
      $remoteTccIds = [];
			foreach ( $data['values'] as $index => $row )
			{
        // Skip empty rows.
        if ( empty( $row[0] ) ) { $blankRows++; continue; }

        // Map row values (no keys) to a TCC key:value array
        $tccData = $tccModel->mapToTccData( $localFieldNames, $row );

        // Add the tcc uid to a hash table with `tcc_id` as key and row index as value
        $tccUid = $tccData['tcc_id'];
        $remoteTccIds[$tccUid] = $index;

        // add sync info before saving to DB
        $tccData['sync_at']   = $now;
        $tccData['sync_by']   = $syncBy;
        $tccData['sync_from'] = 'remote';

        $upsertOptions = ['onchange' => ['created_at', 'updated_at', 'allocated_trades']];
        $result = $app->db->table( 'tccs' )->upsert( $tccData, 'tcc_id', $upsertOptions );
        if ( ! $result ) throw new Exception( 'Failed to save tcc. Index = ' . $index . 
        		' TCC = ' . json_encode( $tccData ) );

        if ( $result['status'] === 'unchanged' ) $unchanged++;
        else if ( $result['status'] === 'updated' ) $updated++;
        else if ( $result['status'] === 'inserted' ) $inserted++;

        // Update progress every $updateEvery tccs
        if ( $index % $updateEvery === 0 && $index !== 0 ) {
          $progress += $progressIncrement;
          $progress = min( 100, $progress ); // Just in case :)
          debug_log( "TCC Index: $index, progress: " . round( $progress, 2 ) . '%', '', 2 );
          updateJobProgress( $jobs, $jobId, floor( $progress ) );
        }

        if ( $index > 50000 ) break; // safety net
       }

 
      // Soft delete tccs that are no longer in the remote database.
      $tccsToDelete = array_diff_key( $localTccIds, $remoteTccIds );

      // Only log the first 10 items of each array.
      debug_log( array_slice( $localTccIds, 0, 10 ), 'Local TCC Ids (First 10): ', 2 );
      debug_log( array_slice( $remoteTccIds, 0, 10 ), 'Remote TCC Ids (First 10): ', 2 );
      debug_log( array_slice( $tccsToDelete, 0, 50 ), 'TCCs to delete (First 50): ', 2 );

      foreach ( $tccsToDelete as $tccUid => $id ) {
        $tccModel->softDelete( $id );
        $deleted++;
      }

      $message = "Fetching $tccsCount tccs completed successfully.<br><small><i>Stats: " .
        "Updated: $updated, Inserted: $inserted, Deleted: $deleted, Unchanged: $unchanged, " .
        "BlankRows: $blankRows<i></small>";

      debug_log( $message, 'Summary message: ', 2 );

      json_response( ['success' => true, 'message' => $message] );
    } // end: fetchTCCs


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