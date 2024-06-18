<?php /* Admin Module - Tools Batch Process SPA - Rollovers Sub Controller */

use App\Models\Client as ClientModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\ClientStatement as ClientStatementModel;

use App\Exceptions\ValidationException;



// -------------
// -- REQUEST --
// -------------

$operationId = $_GET['op'] ?? null;
if ( empty( $operationId ) ) respond_with( 'Bad request', 400 );



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {


    $action = $_POST['action'] ?? '';
    $mode = $_POST['mode'] ?? 'test';

    debug_log( $action, 'IS POST Request - Action: ', 3 );

    $uid = $app->user->user_id;

    $db = use_database();



    /** ACTION 1 **/

    if ( $action === 'processClient' and $mode === 'test' )
    {

      $year = 2023;
      $prevYear = $year - 1;

      $clientUid = $_POST['uid'] ?? null;
      $clientName = $_POST['name'] ?? null;
      if ( ! $clientUid ) respond_with( 'Bad request', 400 );

      $statement = new ClientStatementModel( $app );
      $statement->generate( $clientUid, [ 'year' => 2023 ] );

      $trades = $statement->data['lines'] ?? [];

      $totalSDA = $statement->data['totalSDA'] ?? 0;
      $totalFIA = $statement->data['totalFIA'] ?? 0;
      $tradesAmount = $totalSDA + $totalFIA;

      $stats = [];


      $tradeAnalysis = [];
      $stats['trades'] = count( $trades );
      $stats['SDA'] = $statement->data['SDA'] ?? 0;
      $stats['FIA'] = $statement->data['FIA'] ?? 0;
      $stats['SFA'] = $statement->data['SFA'] ?? 0;
      $stats['trades_sda_amount'] = $totalSDA;
      $stats['trades_fia_amount'] = $totalFIA;
      $stats['trades_amount'] = $tradesAmount;
      foreach ( $trades as $trade ) {
        
        $pins = json_decode( $trade->allocated_pins ?: '[]', true );
        $allocs_sdaPart = $pins['_SDA_'] ?? 0;
        unset( $pins['_SDA_'] );
        $allocs_fiaPart = $pins ? array_sum( $pins ) : 0;
        $allocs_total = $allocs_sdaPart + $allocs_fiaPart;


        // TEST 1
        $amount_match_cover = ( $trade->zar_sent == $trade->amount_covered );

        // TEST 2
        $cover_match_allocs = ( $trade->amount_covered == $allocs_total );

        $tradeStats = [
          'id' => $trade->trade_id,
          // 'date' => $trade->date,
          'amount' => $trade->zar_sent,
          'cover' => $trade->amount_covered,
          // 'allocs' => $trade->allocated_pins,
          // 'allocs_SDA' => $allocs_sdaPart,
          // 'allocs_FIA' => $allocs_fiaPart,
          // 'allocs_total' => $allocs_total,
          'amount_match_cover' => $amount_match_cover,
          'cover_match_allocs' => $cover_match_allocs,
        ];

        // $statPairs = [];
        // foreach ( $tradeStats as $key => $value ) $statPairs[] = "$key=$value";
        // $tradeAnalysis[] = implode( ' | ', $statPairs );
        $tradeAnalysis[] = $tradeStats;
      }

      debug_log( $tradeAnalysis, 'Trade analysis: ', 4 );

      $stats['trades_w_cover_mismatch'] = array_reduce( $tradeAnalysis, function( $sum, $stat ) {
        return $sum + ( $stat['amount_match_cover'] ? 0 : 1);
      }, 0 );

      $stats['trades_w_alloc_mismatch'] = array_reduce( $tradeAnalysis, function( $sum, $stat ) {
        return $sum + ( $stat['cover_match_allocs'] ? 0 : 1);
      }, 0 );

      $rollins = $db->table( 'tccs' )
        ->where( 'deleted_at', 'IS', NULL )
        ->where( 'client_id', $clientUid )
        ->where( 'YEAR(`date`)', $prevYear )
        ->where( 'rollover', '>', 0 )
        ->getAll();

      $stats['RIs'] = count( $rollins );

      $stats['rollins_amount'] = array_reduce( $rollins, function( $sum, $tcc ) {
        return $sum + $tcc->rollover;
      }, 0 );

      $tccs = $db->table( 'tccs' )
        ->where( 'deleted_at', 'IS', NULL )
        ->where( 'client_id', $clientUid )
        ->where( 'YEAR(`date`)', $year )
        ->where( 'status', 'IN', ['Approved', 'Expired'] )
        ->getAll();

      $stats['TCCs'] = count( $tccs );

      $stats['ROs'] = 0;
      $stats['tccs_amount'] = 0;
      $stats['rollover_amount'] = 0;
      foreach ( $tccs as $tcc )
      {
        $stats['tccs_amount'] += $tcc->amount_cleared;
        $stats['rollover_amount'] += $tcc->rollover;
        if ( $tcc->rollover > 0 ) $stats['ROs']++;
      }

      $data = [];
      $data['client'] = $clientName;
      $data['annual'] = (array) $statement->data['annualInfo'];
      unset( $data['annual']['id'] );
      $data['stats'] = $stats;

      debug_log( $data, 'Response data: ', 2 );

      json_response( [ 'success' => true, 'data' => $data, 'message' => 'processed ok' ] );

    } // processClient



    /** ACTION 2 **/

    if ( $action === 'processClient' and $mode === 'update' )
    {

      $clientUid = $_POST['uid'] ?? null;
      $clientName = $_POST['name'] ?? null;
      if ( ! $clientUid ) respond_with( 'Bad request', 400 );

      $clientModel = new ClientModel( $app );
      $client = $clientModel->getClientByUid( $clientUid );
      if ( ! $client ) respond_with( "Client uid=$clientUid not found.", 404 );
      debug_log( $client, 'Client to update: ', 2 );

      $db->pdo->beginTransaction();

      // $updateOptions = [];
      // $updateOptions['year'] = 2023;
      // $updateOptions['redoAllocations'] = true;

      // $clientStateModel = new ClientStateModel( $app );
      // $updateResult = $clientStateModel->updateStateFor( $client, $updateOptions );
      // debug_log( $updateResult, 'Redo Client SDA/FIA Allowances 2023 Result: ', 3 );

      // $updateOptions = [];
      // $updateOptions['year'] = 2024;
      // $updateOptions['redoAllocations'] = true;

      // $clientStateModel = new ClientStateModel( $app );
      // $updateResult = $clientStateModel->updateStateFor( $client, $updateOptions );
      // debug_log( $updateResult, 'Redo Client SDA/FIA Allowances 2024 Result: ', 3 );

      // $newPinsThisYear = $clientModel->getClientTccPins( $client, 'Approved', [ 'year' => 2023 ] );
      // $pinsWithAmountAvailable = array_filter( $newPinsThisYear, function( $pin ) {
      //   return $pin->amount_available > 0 and $pin->rollover < $pin->amount_available;
      // });
      // debug_log( $pinsWithAmountAvailable, 'Pins with amount available: ' );

      // foreach ( $pinsWithAmountAvailable as $pin ) {
      //   $pin->rollover = $pin->amount_available;
      //   $pin->notes = $pin->notes . ' | auto rollover';
      //   $db->table( 'tccs' )->update( (array) $pin, $updateOptions );
      // }

      // if ( $pinsWithAmountAvailable ) {
        $updateOptions = [];
        $updateOptions['year'] = 2024;
        $clientStateModel = new ClientStateModel( $app );
        $updateResult = $clientStateModel->updateStateFor( $client, $updateOptions );
        debug_log( $updateResult, 'Update Client SDA/FIA Allowances 2024 Result: ', 4 );
      // }

      $app->db->pdo->commit();

      $data = [];
      $data['client'] = $clientName;

      debug_log( $data, 'Response data: ', 2 );

      json_response( [ 'success' => true, 'data' => $data, 'message' => 'Client data updated successfully.' ] );      
    }



    // /** ACTION 3 **/

    // if ( $action === 'updateMandates' )
    // {

    //   debug_log( 'updateMandates, Start... ' );

    //   $clients23 = $db->table( 'clients_2023_view' )->getAll();

    //   $db->pdo->beginTransaction();

    //   foreach ( $clients23 as $c23 ) {
    //     $mandate = [
    //       'client_id' => $c23->client_id2,
    //       'year' => 2023,
    //       'sda_mandate' => $c23->sda_mandate,
    //       'fia_mandate' => $c23->fia_mandate,
    //       'trading_capital' => $c23->trading_capital,
    //       'final_statement_file' => $c23->statement_pdf,
    //       'google_statement_link' => $c23->statement_file,
    //     ];

    //     $db->table('clients_annual_info')->insert($mandate);
    //   }

    //   $db->pdo->commit();

    //   json_response( [ 'success' => true, 'message' => 'Update done.' ] );

    // } // updateMandates



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

$db = use_database();

$operation = $db->getFirst( 'batch_operations', $operationId );

$clients = $db->table( 'clients' )
  ->where( 'status', 'active' )
  ->getAll();
