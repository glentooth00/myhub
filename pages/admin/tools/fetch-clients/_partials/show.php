<?php /* Admin SPA - Tools Fetch Clients Show - Sub Controller */

use F1\FileSystem;
use App\Models\ClientS2Mapper;
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

    $jobId = $_POST['jobId'] ?? null;



    /** ACTION 1 **/

    /* Fetch Job Progress while updating the clients db table */

    if ( $action === 'fetchProgress' )
    {
      $jobs = $_SESSION['jobs'] ?? [];
      $noJob = new stdClass(); $noJob->progress = 0;
      $job = isset( $jobs[ $jobId ] ) ? $jobs[ $jobId ] : $noJob;
      debug_log( "fetchProgress(), Job Id: $jobId, progress: {$job->progress}%", '', 2 );

      json_response( [ 'success' => true,  'data' => $job ] );

    } // fetchProgress



    /** ACTION 2 **/

    if ( $action === 'fetchClients' )
    {
      $logDir = $app->storageDir . __DS__ . 'logs' . __DS__ . 'fetch-clients';
      $logFile = 'u' . $app->user->id . '_fetch_' . date( 'Ymd_His' ) . '.txt';
      $app->logger = new App\Services\AppLogger( $app, $logDir, $logFile );

      debug_log( PHP_EOL, '', 2 );      
      // debug_log( $app->user, 'IS POST Request - User: ', 2 );
      debug_log( $_POST, 'fetchClients(), POST: ', 2 );

      $progress = 0;
      $now = date('Y-m-d H:i:s');
      $expiresAt = date( 'Y-m-d H:i:s', strtotime( '+1 hour' ) );

      $job = new stdClass();
      $job->desc = 'Fetch and update `clients` table from Google Sheet.';
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
        __GOOGLE_ALL_CLIENTS_RANGE__
      );

      $progress += 20;
      updateJobProgress( $jobs, $jobId, $progress );

      $headers = array_shift( $data['values'] );

      debug_log( $headers, 'Headers: ', 2 );

			$clientsCount = count( $data['values'] );
			debug_log( $clientsCount, 'Clients fetched: ', 2 );
			debug_log( $data['values'][0], 'First client fetched: ', 2 );

      $syncBy = $app->user->user_id;

      $clientModel = new ClientModel( $app );
      $clientS2Mapper = new ClientS2Mapper( $app );

      $clientFieldNames = $clientModel->getDbColumnNames();

      $localFieldNames = $clientS2Mapper->removeLocalOnlyFields( $clientFieldNames );
      debug_log( $localFieldNames, 'Local db field mames (without id and sync fields): ', 2 );

      $remoteFieldNames = $clientS2Mapper->mapGoogleTableHeaders( $headers );
      debug_log( $remoteFieldNames, 'Remote data fields (mapped from headers): ', 2 );

      $fields_diff = $clientS2Mapper->getFieldsMismatch( $localFieldNames, $remoteFieldNames );
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

      // Get all local client ids as a hash table with `client_id` as key and `id` as value
      $localClientIds = $clientModel->getClientIdsByUid();

      $updateEvery = 200; // clients
      $remainingProgress = 100 - $progress;
      $numberOfUpdates = floor( $clientsCount / $updateEvery );
      $progressIncrement = $numberOfUpdates
        ? floor( $remainingProgress / $numberOfUpdates )
        : $remainingProgress;

      $app->db->pdo->beginTransaction();

      // Loop through each row of data and insert into our database
      $updated = 0;
      $deleted = 0;
      $inserted = 0;
      $unchanged = 0;
      $blankRows = 0;       
      $remoteClientIds = [];
			foreach ( $data['values'] as $index => $row )
			{
        // Skip empty rows.
        if ( empty( $row[0] ) ) { $blankRows++; continue; }

        // Map row values array (no keys) to a Client key:value array
        $client = $clientS2Mapper->mapRemoteRowToClient( $row, $localFieldNames );

        // Add the client uid to a hash table with `client_id` as key and row index as value
        $remoteClientIds[$client->client_id] = $index;

        // add sync info before saving to DB
        $client->sync_at    = $now;
        $client->sync_by    = $syncBy;
        $client->sync_from  = 'remote';
        $client->created_at = $client->created_at ?: $now;

        $upsertOptions = ['onchange' => ['created_at', 'updated_at']];
        $result = $app->db->table( 'clients' )->upsert( (array) $client, 'client_id', $upsertOptions );
        if ( ! $result ) throw new Exception( 'Failed to save client. Index = ' . $index . 
        		' Client = ' . json_encode( $client ) );

        if ( $result['status'] === 'unchanged' ) $unchanged++;
        else if ( $result['status'] === 'updated' ) $updated++;
        else if ( $result['status'] === 'inserted' ) $inserted++;          

        // Update progress every $updateEvery clients
        if ( $index % $updateEvery === 0 && $index !== 0 ) {
          $progress += $progressIncrement;
          $progress = min( 100, $progress ); // Just in case :)
          debug_log( "Client Index: $index, progress: $progress%", '', 2 );
          updateJobProgress( $jobs, $jobId, $progress );
        }

        if ( $index > 10000 ) break; // safety net
       }


      // Soft delete clients that are no longer in the remote database.
      $clientsToDelete = array_diff_key( $localClientIds, $remoteClientIds );

      // Only log the first 10 items of each array.
      debug_log( array_slice( $localClientIds, 0, 10 ), 'Local Client Ids (First 10): ', 2 );
      debug_log( array_slice( $remoteClientIds, 0, 10 ), 'Remote Client Ids (First 10): ', 2 );
      debug_log( array_slice( $clientsToDelete, 0, 50 ), 'Clients to delete (First 50): ', 2 );

      foreach ( $clientsToDelete as $clientId => $id ) {
        $clientModel->softDelete( $id );
        $deleted++;
      }

      $message = "Fetching $clientsCount clients completed successfully.<br><small><i>Stats: " .
        "Updated: $updated, Inserted: $inserted, Deleted: $deleted, Unchanged: $unchanged, " .
        "BlankRows: $blankRows<i></small>";

      debug_log( $message, 'Summary message: ', 2 );

      $app->db->pdo->commit();

      json_response( ['success' => true, 'message' => $message] );

    } // fetchClients



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    $app->db->safeRollBack();
    json_response( [ 'success' => false, 'message' => $error ] );
  }

}




// ---------
// -- GET --
// ---------