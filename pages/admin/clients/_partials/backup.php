<?php /* Admin SPA - Client Backup - Sub Controller */


function getClientBackupsDir( $uploadsDir, $client )
{
  $clientDirectory = $uploadsDir . __DS__ . $client->name . '_' . $client->id . __DS__ . 'backups';
  if ( ! is_dir( $clientDirectory ) ) mkdir( $clientDirectory, 0777, true );
  return $clientDirectory;
}


function backupData( $app, $client )
{
  $db = $app->db;
  $backup = [];
  $backup['timestamp'] = date( 'Y-m-d H:i:s' );
  $backup['stats'] = [ 'tccs' => 0, 'trades' => 0 ];
  $backup['client'] = $client;
  $backup['tccs'] = $db->table( 'tccs' )->where( 'client_id', $client->client_id )->getAll();
  $backup['trades'] = $db->table( 'trades' )->where( 'client_id', $client->client_id )->getAll();
  $backup['stats']['tccs'] = count( $backup['tccs'] );
  $backup['stats']['trades'] = count( $backup['trades'] );
  $timestamp = date( 'YmdHis' );
  $backupsDir = getClientBackupsDir( $app->uploadsDir, $client );
  $filename = $backupsDir . __DS__ . $timestamp . '_' . $client->client_id . '_bkp.json';
  $backupJson = json_encode( $backup, JSON_PRETTY_PRINT );
  file_put_contents( $filename, $backupJson );
  return $filename;
}


function restoreData( $app, $user, $filename )
{
  $backup = json_decode( file_get_contents( $filename ), true );
  if ( ! $backup ) return false;

  $now = date( 'Y-m-d H:i:s' );
  $syncBy = $user->username;
  $user = $app->user;
  $db = $app->db;
  $messages = [];


  // Restore client

  $clientData = $backup['client'];

  $clientData['sync_at']   = $now;
  $clientData['sync_by']   = $syncBy;
  $clientData['sync_from'] = 'backup';  

  $result = $app->db->table( 'clients' )->upsert( $clientData, 'client_id' );
  if ( ! $result ) throw new Exception( 'Failed to restore client. ' . 
      ' Client = ' . json_encode( $clientData ) );

  $message = "Restoring client record completed.";
  debug_log( $message );

  $messages[] = $message;


  // Restore TCCs

  $currentTccs = $db->table( 'tccs' )->where( 'client_id', $clientID )->getAll();

  $currentTccUUIDs = [];
  foreach ( $currentTccs as $tcc ) $currentTccUUIDs[] = $tcc->tcc_id;

  $updated = 0;
  $inserted = 0;
  $deleted = 0;
  foreach ( $backup['tccs']??[] as $index => $tccData )
  {
    $tccUUID = $tccData['tcc_id'] ?? null;

    if ( ! $tccUUID ) continue;

    // Check if tccUUID is within current Tcc UUID's array
    $tccIndex = array_search( $tccUUID, $currentTccUUIDs );

    // If not, delete the current Tcc
    if ( $tccIndex === false ) {
      $db->table( 'tccs' )->where( 'tcc_id', $tccUUID )->delete();
      $deleted++;
      continue;
    }

    $tccData['sync_at']   = $now;
    $tccData['sync_by']   = $syncBy;
    $tccData['sync_from'] = 'backup';

    $result = $app->db->table( 'tccs' )->upsert( $tccData, 'tcc_id' );
    if ( ! $result ) throw new Exception( 'Failed to restore tcc. Index = ' . $index . 
        ' TCC = ' . json_encode( $tccData ) );

    if ( $result['status'] === 'updated' ) $updated++;
    else if ( $result['status'] === 'inserted' ) $inserted++;
  }

  $tccsCount = count( $backup['tccs'] );
  $message = "Restoring $tccsCount tccs completed.<br><small><i>Stats: " .
    "Updated: $updated, Inserted: $inserted, Deleted: $deleted<i></small>";

  debug_log( $message );

  $messages[] = $message;


  // Restore Trades

  $currentTrades = $db->table( 'trades' )->where( 'client_id', $clientID )->getAll();

  $currentTradeUUIDs = [];
  foreach ( $currentTrades as $trade ) $currentTradeUUIDs[] = $trade->trade_id;

  $updated = 0;
  $inserted = 0;
  $deleted = 0;
  foreach ( $backup['trades']??[] as $index => $tradeData )
  {
    $tradeUUID = $tradeData['trade_id'] ?? null;

    if ( ! $tradeUUID ) continue;

    // Check if tradeUUID is within current Trade UUID's array
    $tradeIndex = array_search( $tradeUUID, $currentTradeUUIDs );

    // If not, delete the current Trade
    if ( $tradeIndex === false ) {
      $db->table( 'trades' )->where( 'trade_id', $tradeUUID )->delete();
      $deleted++;
      continue;
    }

    $tradeData['sync_at']   = $now;
    $tradeData['sync_by']   = $syncBy;
    $tradeData['sync_from'] = 'backup';

    $result = $app->db->table( 'trades' )->upsert( $tradeData, 'trade_id' );
    if ( ! $result ) throw new Exception( 'Failed to restore trade. Index = ' . $index . 
        ' Trade = ' . json_encode( $tradeData ) );

    if ( $result['status'] === 'updated' ) $updated++;
    else if ( $result['status'] === 'inserted' ) $inserted++;
  }

  $tradesCount = count( $backup['trades'] );
  $message = "Restoring $tradesCount trades completed.<br><small><i>Stats: " .
    "Updated: $updated, Inserted: $inserted, Deleted: $deleted<i></small>";

  debug_log( $message );

  $messages[] = $message;

  return $messages;

}




// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? null;

if ( ! is_numeric( $id ) ) respond_with( 'Bad request', 400 );




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $_FILES, 'IS POST Request - FILES: ', 3 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );
  
  try {

    $now = date( 'Y-m-d H:i:s' );

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 2 );


    /** ACTION 1 **/

    if ( $action === 'backupData' ) {
      use_database();
      $client = $app->db->getFirst( 'clients', $id );
      $filename = backupData( $app, $client );
      $message = 'Backup complete. File: ' . basename( $filename );
      json_response( [ 'success' => true, 'message' => $message, 'filename' => $filename ] );
    } // backupData



    /** ACTION 2 **/

    if ( $action === 'downloadFile' ) {
      use_database();
      $client = $app->db->getFirst( 'clients', $id );      
      $filename = $_POST['filename'] ?? null;
      download_response( $filename );
    } // downloadFile



    /** ACTION 3 **/

    if ( $action === 'restoreData' ) {
      use_database();
      $client = $app->db->getFirst( 'clients', $id );
      $file = $_FILES['file'] ?? null;
      $fileEmpty = $file ? empty( $file['name'] ) : true;
      $uploadTarget = null;
      if ( ! $fileEmpty ) {
        $fileType = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( $fileType != 'json' ) throw new Exception( 'Please upload a JSON file.' );
        if ( $file['size'] > 2097152 ) throw new Exception( 'File size must not exceed 2MB.' );
        $filename = $file['tmp_name'];

        $app->db->pdo->beginTransaction();
        
        $messages = restoreData( $app, $app->user, $filename );
        // Create a combined message
        $message = '';
        foreach ( $messages as $msg ) $message .= $msg . '<br>';

        $app->db->pdo->commit();

        json_response( [ 'success' => true, 'message' => $message ] );
      }
    } // restoreData



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    $app->db->safeRollBack();
    abort_uploads();
    json_response( [ 'success' => false, 'message' => $error ] );
  } 

}




// ---------
// -- GET --
// ---------

use_database();
$client = $app->db->getFirst( 'clients', $id );