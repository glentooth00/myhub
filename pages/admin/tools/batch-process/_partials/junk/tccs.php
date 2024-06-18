<?php /* Admin Tools - Process Client Tccs Page Controller */

use F1\Database;
use F1\FileSystem;


// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  // debug_log( $app->user, 'IS POST Request - User: ' );

  if ( ! $app->request->isAjax )
    $app->response->exitWithHtml( 'Bad request', 400 );  

  try {

    $action = $app->request->getPostVal( 'action' );


    /* Process Clients */

    if ( $action === 'processClientTccs' )
    {

    } // END: processClientTccs()

    throw new Exception( 'Invalid or missing request action.' );

  }

  catch ( Exception $ex ) {
    $app->logger->log( $ex->getMessage(), 'error' );
    $file = $ex->getFile();
    $line = $ex->getLine();
    $message = $ex->getMessage();
    $message .= "<br>---<br>Error on line: $line of $file";
    $app->response->exitWithJson( [ 'success' => false, 'message' => $message ] );
  }

}



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

// CREATE TABLE `tcc_trades` (

// ---------
// -- GET --
// --------- 


function getTotalAllocated( $allocatedTrades )
{
  $trades = $allocatedTrades ? json_decode( $allocatedTrades, true ) : [];
  return $trades ? array_sum( $trades ) : 0;  
}


function validateTcc( $tcc )
{
  $tcc->totalAllocated = getTotalAllocated( $tcc->allocated_trades );

  if ( $tcc->totalAllocated != $tcc->amount_used )
    return 'Amount Used does not match the Allocated Total.';

  return '';
}


$app->db = new Database( $app->config->get( 'db' ) );


$action = 'process2022Tccs';


if ( $action == 'process2023Tccs' ) {

  $tccs = $app->db->table( 'tccs' )
    ->where('`date`', '>=', '2023-01-01')
    ->orderBy('`date`')
    ->getAll();

  foreach ($tccs as $tcc) {
    $tcc->error = validateTcc( $tcc );
    $tcc->valid = $tcc->error === '' ? 'Yes' : 'No';
    if ( $tcc->valid == 'No' ) {
      if ( $tcc->amount_cleared == $tcc->totalAllocated ) {
        $tcc->notes = $tcc->notes . '| rollover was = ' . $tcc->rollover;
        $tcc->rollover = 0;
        $tcc->amount_remaining = 0;
        $tcc->amount_available = 0;
        $tcc->amount_cleared_net = $tcc->amount_cleared;
        $tcc->amount_used = $tcc->totalAllocated;
        $tcc->amount_reserved = 0;
        $tcc->status = 'Approved';
        $tcc->error = 'Rollover was = ' . $tcc->rollover;
        try {
          $app->db->pdo->beginTransaction();
          $app->db->table('tccs')->save( (array) $tcc );
          $app->db->pdo->commit();
        }
        catch ( Exception $ex ) {
          $app->db->pdo->rollBack();
          $app->logger->log( $ex->getMessage(), 'error' );
          $file = $ex->getFile();
          $line = $ex->getLine();
          $message = $ex->getMessage();
          $message .= "<br>---<br>Error on line: $line of $file";
          die( $message );
        }
      }
      if ( $tcc->totalAllocated > 0 and $tcc->totalAllocated < $tcc->amount_cleared ) {
        $tcc->amount_remaining = $tcc->amount_cleared - $tcc->totalAllocated;
        $tcc->rollover = $tcc->amount_remaining;
        $tcc->status = 'Approved';
        $tcc->error = 'Rollover was = ' . $tcc->rollover;
        $tcc->notes = $tcc->notes . '| rollover was = ' . $tcc->rollover;
        $tcc->amount_cleared_net = $tcc->rollover;
        $tcc->amount_available = $tcc->amount_remaining;
        $tcc->amount_used = $tcc->totalAllocated;
        $tcc->amount_reserved = 0;
        try {
          $app->db->pdo->beginTransaction();
          $app->db->table('tccs')->save( (array) $tcc );
          $app->db->pdo->commit();
        }
        catch ( Exception $ex ) {
          $app->db->pdo->rollBack();
          $app->logger->log( $ex->getMessage(), 'error' );
          $file = $ex->getFile();
          $line = $ex->getLine();
          $message = $ex->getMessage();
          $message .= "<br>---<br>Error on line: $line of $file";
          die( $message );
        }
      }
      if ( $tcc->status == 'Approved' and ! $tcc->amount_remaining ) {
        $tcc->error = 'Expired was = ' . $tcc->expired;
        $tcc->notes = $tcc->notes . '| expired was = ' . $tcc->expired;
        $tcc->expired = 2023;
        try {
          $app->db->pdo->beginTransaction();
          $app->db->table('tccs')->save( (array) $tcc );
          $app->db->pdo->commit();
        }
        catch ( Exception $ex ) {
          $app->db->pdo->rollBack();
          $app->logger->log( $ex->getMessage(), 'error' );
          $file = $ex->getFile();
          $line = $ex->getLine();
          $message = $ex->getMessage();
          $message .= "<br>---<br>Error on line: $line of $file";
          die( $message );
        }
      }

    }
  }

} // END: process2023Tccs


if ( $action == 'process2022Tccs' ) {

  $tccs = $app->db->table( 'tccs' )
    ->where( 'status', '=', 'Approved' )
    ->where( '`date`', '>=', '2022-01-01' )
    ->where( '`date`', '<', '2023-01-01' )
    ->orderBy( '`date`' )
    ->getAll();


  foreach ($tccs as $tcc) {
    
    $tcc->expired = 2023;
    $tcc->status = 'Expired';
    $tcc->valid = 'No';
    $tcc->error = 'Set expired = ' . $tcc->expired;
    $tcc->notes = $tcc->notes . '|' . $tcc->error;
    $tcc->totalAllocated = getTotalAllocated( $tcc->allocated_trades );
    $tcc->amount_used = $tcc->totalAllocated;
    $tcc->amount_cleared_net = $tcc->rollover ?: $tcc->amount_cleared;
    $tcc->amount_remaining = $tcc->amount_cleared_net - $tcc->amount_used;
    $tcc->amount_available = 0;
    $tcc->amount_reserved = 0;
    try {
      $app->db->pdo->beginTransaction();
      $app->db->table('tccs')->save( (array) $tcc );
      $app->db->pdo->commit();
    }
    catch ( Exception $ex ) {
      $app->db->pdo->rollBack();
      $app->logger->log( $ex->getMessage(), 'error' );
      $file = $ex->getFile();
      $line = $ex->getLine();
      $message = $ex->getMessage();
      $message .= "<br>---<br>Error on line: $line of $file";
      die( $message );
    }

  }

  debug_dump($tccs, 'tccs');


} // END: process2022Tccs
