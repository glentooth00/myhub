<?php /* Admin Module - Tools Batch Process SPA - Trades Sub Controller */

use App\Models\ClientState as ClientStateModel;
use App\Models\ClientS2Response as ClientS2ResponseModel;



// -------------
// -- REQUEST --
// -------------

$operationId = $_GET['op'] ?? null;
if ( empty( $operationId ) ) respond_with( 'Bad request', 400 );



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  function updateAndSyncClient( $clientUid )
  {
    global $app;

    $noSync = true;

    $year = date( 'Y' );

    $client = $app->db->table( 'clients' )->where( 'client_id', $clientUid )->getFirst();
    if ( ! $client ) throw new Exception( "Client uid=$clientUid not found." );
    debug_log( $client, 'Client to update (before s2 rpc): ', 2 );

    $opts = [];
    $opts['redoAllocations'] = null;
    $opts['setRollovers'] = null;
    $opts['year'] = null;

    $clientStateModel = new ClientStateModel( $app );
    $updateResult = $clientStateModel->updateStateFor( $client, $opts );
    debug_log( $updateResult, 'Client Update State Result: ', 3 ); 

    if ( $noSync ) return;

    // ---------------
    // Sync Remote: S2
    // ---------------

    $responseModel = new ClientS2ResponseModel( $app );
    $payload = $responseModel->generate( $updateResult );

    $result = run_google_script( 'updateClient', $payload, 'neels@currencyhub.co.za' );

    $links = $result['links'] ?? null;

    if ( $links ) {
      $linkData = [];
      if ( isset( $links['smtUrl'] ) ) $linkData['statement_file'] = $links['smtUrl'];
      if ( isset( $links['pdfUrl'] ) ) $linkData['statement_pdf'] = $links['pdfUrl'];        
      if ( $linkData ) {
        $linkData['id'] = $client->id;
        debug_log( $linkData, 'Update client link data: ', 3 );
        $app->db->table( 'clients' )->update( $linkData );
      }
    } else {
      debug_log( $result, 'WARNING: No "Statement Links" in Google API "updateClient" Result.', 2 );
    }

  } // updateAndSyncClient


  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {


    $action = $_POST['action'] ?? '';
    $mode = $_POST['mode'] ?? 'test';

    debug_log( $action, 'IS POST Request - Action: ', 3 );

    $uid = $app->user->user_id;

    $db = use_database();
    $app->db = $db;



    /** ACTION 1 **/

    if ( $action === 'fetchTrades' )
    {

      $query = $_POST['query'] ?? '';
      if ( ! $query ) json_response( [ 'success' => false, 'message' => 'Invalid query' ] );

      // Check if query is an SQL Statement
      if ( ! preg_match( '/^SELECT/', $query ) ) {
        // Maybe it's a list of trade IDs seperated by line breaks or commas
        $list = str_replace( [ "\r", "\n" ], ',', $query );
        $list = preg_replace( '/\s*,\s*/', ',', $list );
        $list = explode( ',', $list );
        $list = array_map( 'trim', $list );
        $list = array_filter( $list, 'strlen' );
        $list = array_map( function( $item ) { return "'$item'"; }, $list );
        $list = implode( ',', $list );
        debug_log( $list, 'Trade IDs List: ', 3 );
        $query = "SELECT * FROM view_trades WHERE trade_id IN ($list)";
      }

      $results = $db->query( $query );

      json_response( [ 'success' => true, 'message' => 'Fetch Trades Ok', 'data' => $results ] );

    } // fetchTrades



    /** ACTION 2 **/

    if ( $action === 'fixR500Issue' )
    {

      $tradeId = $_POST['tradeId'] ?? null;
      $trade = $db->getFirst( 'view_trades', $tradeId );
      $client = $trade->client_name;
      $tradeUID = $trade->trade_id;
      $isFIA = $tradeUID != '_SDA_';

      $db->pdo->beginTransaction();

      if ( $isFIA )
      {
        $tradeTccs = $db->table( 'tccs' )->where( 'allocated_trades', 'LIKE', "%$tradeUID%" )->getAll();
  
        debug_log( count( $tradeTccs ), 'Trade TCCs: ', 3 );

        foreach( $tradeTccs as $tcc ) {
          // $tcc->allocated_trades = JSON string like e.g {"54457C223I":214500,"AG488C723F":36000}
          $tcc->allocated_trades = json_decode( $tcc->allocated_trades, true );
          debug_log( $tcc->allocated_trades, 'TCC Allocated Trades (before): ', 3 );
          $value = $tcc->allocated_trades[$tradeUID] ?? 0;
          unset( $tcc->allocated_trades[$tradeUID] );
          $tccTime = strtotime( $tcc->date );
          $isExpired = $tccTime < strtotime( '-1 year' );
          $tcc->allocated_trades = count( $tcc->allocated_trades ) ? json_encode( $tcc->allocated_trades ) : null;
          $tcc->amount_used = $tcc->amount_used - $value;
          $tcc->amount_remaining = $tcc->amount_remaining + $value;
          $tcc->amount_available = $isExpired ? 0 : $tcc->amount_available + $value;

          if ( ! $tcc->amount_available and $tcc->amount_used ) $tcc->amount_available = null;
          if ( ! $tcc->amount_remaining and $tcc->amount_used ) $tcc->amount_remaining = null;
          if ( ! $tcc->rollover ) $tcc->rollover = null;

          if ( $tcc->amount_available and $tcc->expired ) { 
            $tcc->expired = $isExpired ? date( $tccTime, 'Y' ) : null;
            $tcc->status = $isExpired ? 'Expired' : 'Approved'; 
          }

          debug_log( $tcc->allocated_trades, 'TCC Allocated Trades (after): ', 3 );
          $tccUpdateResult = $db->table( 'tccs' )->update( (array) $tcc, ['autoStamp' => true, 'user' => $app->user->user_id ] );
          debug_log( $tccUpdateResult, 'Update TCC Result: ', 3 );
        }
      }

      $trade->zar_sent -= 500;
      $trade->amount_covered = 0;
      $trade->zar_profit += 500;
      $trade->allocated_pins = null;
      $trade->percent_return = round( $trade->zar_profit / $trade->zar_sent, 4 ) * 100;
      
      $tradeUpdateResult = $db->table( 'trades' )->update( (array) $trade, 
        ['autoStamp' => true, 'user' => $app->user->user_id ] );
      debug_log( $tradeUpdateResult, 'Update Trade Result: ', 3 );

      updateAndSyncClient( $trade->client_id );

      $db->pdo->commit();

      json_response( [ 'success' => true, 'message' => "$tradeUID, $client: R500 Issue Fixed, TCCs: " . count( $tradeTccs ) ] );

    } // fixR500Issue



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $app->logger->log( $ex->getMessage(), 'error' );
    $db->safeRollBack();
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