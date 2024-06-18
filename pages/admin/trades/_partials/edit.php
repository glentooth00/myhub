<?php /* Admin Module - Trades SPA - Add/Edit Trade Sub Controller */

allow( 'super' );


use App\Services\AppForm;

use App\Models\Tcc as TccModel;
use App\Models\Trade as TradeModel;
use App\Models\Client as ClientModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\ClientS2Response as ClientS2ResponseModel;
use App\Models\TradeS2Mapper as TradeS2MapperModel;

use App\Exceptions\ValidationException;


function array_find( $array, $key, $value )
{
  foreach( $array as $item ) {
    if ( $item->$key == $value ) return $item;
  }
  return null;
}


function toSqlDateFormat( $dateString )
{
  // Parse either 'Y-m-d' or 'd/m/Y' format
  $date = DateTime::createFromFormat('Y-m-d', $dateString) ?: 
    DateTime::createFromFormat('d/m/Y', $dateString);
  return $date ? $date->format('Y-m-d') : false;
}


function getAllocsDetail( $app, $allocatedPinsJson, $validate = false )
{
  debug_log( $allocatedPinsJson, 'getAllocsDetail(), Allocated Pins JSON: ' );
  debug_log( $validate ? 'Y' : 'N', 'getAllocsDetail(), Validate: ' );

  $pinAmounts = $allocatedPinsJson ? json_decode( $allocatedPinsJson, true ) : [];
  if ( ! is_array( $pinAmounts ) ) $pinAmounts = [];

  $pins = [];
  $pinNumbers = [];
  $totalCover = 0;

  if ( isset( $pinAmounts['_SDA_'] ) ) {
    $pins['_SDA_'] = null;
    $totalCover += floatval( $pinAmounts['_SDA_'] );
    unset( $pinAmounts['_SDA_'] );
  }

  foreach( $pinAmounts as $pinNo => $coverAmount ) {
    $pinNumbers[] = $pinNo;
    $totalCover += floatval( $coverAmount );
  }

  $tccModel = new TccModel( $app );
  $tccs = $tccModel->getTccsByPin( $pinNumbers );
  if ( $validate and count( $tccs ) != count( $pinAmounts ) ) {
    $missingPins = array_values( array_diff( $pinAmounts, array_column( $tccs, 'tcc_pin' ) ) );
    throw new Exception( 'Some tccs(s) allocated could not be found. ' . json_encode( $missingPins ) );
  }

  foreach( $tccs as $tcc ) { $pins[ $tcc->tcc_pin ] = $tcc; }

  return compact( 'totalCover', 'pins', 'pinAmounts'  );
}



// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? 'new';

if ( ! is_numeric( $id ) and $id !== 'new' )
  respond_with( 'Bad request', 400 );

$isNew = ( $id === 'new' or $id < 0 );
$isEdit = ! $isNew;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $_FILES, 'IS POST Request - FILES: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );


  $now = date( 'Y-m-d H:i:s' );

  $action = $_POST['action'] ?? '';
  debug_log( $action, 'IS POST Request - Action: ', 2 );


  /** ACTION 1 **/

  if ( $action == 'saveTrade' ) {

    try {

      // ---------
      // Validate
      // ---------

      $fileSaveAs = null;
      $fileInfo = isset( $_FILES['file'] ) ? $_FILES['file'] : null;
      $fileEmpty = $fileInfo ? empty( $fileInfo['name'] ) : true;

      $tradeId = isset( $_POST['trade_id'] ) ? $_POST['trade_id'] : null;
      if ( ! $tradeId ) throw new ValidationException(
        [ 'trade_id' => 'Trade ID is required.' ] );

      $clientId = isset( $_POST['client_id'] ) ? $_POST['client_id'] : null;
      if ( ! $clientId ) throw new ValidationException(
        [ 'client_id' => 'Please select a client.' ] );

      $clientModel = new ClientModel( $app );
      $client = $clientModel->getClientByUid( $clientId );
      if ( ! $client ) throw new Exception( 'Client not found.' );

      $tradeModel = new TradeModel( $app );

      // Check if the PIN Number is not already used for another Trade
      $trade = $tradeModel->getTradeByUid( $tradeId );
      debug_log( $trade, 'getTradeByUid(), trade = ', 2 );

      if ( $trade and $trade->trade_id != $tradeId ) throw new ValidationException(
        [ 'trade_id' => 'This "Trade ID" is already used for trade: ' . $trade->id ] );

      $type = isset( $_POST['sda_fia'] ) ? $_POST['sda_fia'] : null;
      if ( ! $type ) throw new ValidationException(
        [ 'sda_fia' => 'Please select a type.' ] );

      $date = isset( $_POST['date'] ) ? $_POST['date'] : null;
      $date = toSqlDateFormat( $date );
      if ( ! $date ) throw new ValidationException(
        [ 'date' => 'Date is required.' ] );

      // Date can not be in the future
      if ( $date > date('Y-m-d') ) throw new ValidationException(
        [ 'date' => 'Date can not be in the future.' ] ); 

      $amount = decimal( $_POST['zar_sent'] ?? '', 0 );
      if ( ! $amount ) throw new ValidationException(
        [ 'zar_sent' => 'Amount is required.' ] );

      $usdBought = decimal( $_POST['usd_bought'] ?? '', 0 );
      if ( ! $usdBought ) throw new ValidationException(
        [ 'usd_bought' => 'TUSD Bought is required.' ] );

      $forexRate = decimal( $_POST['forex_rate'] ?? '', 0 );
      if ( ! $forexRate ) throw new ValidationException(
        [ 'forex_rate' => 'Forex Rate is required.' ] );

      $zarProfit = decimal( $_POST['zar_profit'] ?? '', 0 );
      if ( ! $zarProfit ) throw new ValidationException(
        [ 'zar_profit' => 'ZAR Profit is required.' ] );

      $percentReturn = decimal( $_POST['percent_return'] ?? '', 0 );
      if ( ! $percentReturn ) throw new ValidationException(
        [ 'percent_return' => 'Percent Return is required.' ] );

      $tradeFee = decimal( $_POST['trade_fee'] ?? '', 0 );

      $amountCovered = decimal( $_POST['amount_covered'] ?? '', 0 );

      $postedAllocsJson = isset( $_POST['allocated_pins'] ) ? $_POST['allocated_pins'] : null;

      if ( $postedAllocsJson and ! $amountCovered ) throw new ValidationException(
        [ 'amount_covered' => 'Amount Covered is required.' ] );


      $_POST['zar_sent'] = $amount;
      $_POST['usd_bought'] = $usdBought;
      $_POST['forex_rate'] = $forexRate;
      $_POST['zar_profit'] = $zarProfit;
      $_POST['percent_return'] = $percentReturn;
      $_POST['trade_fee'] = $tradeFee;
      $_POST['date'] = $date;

      if ( $isEdit ) $_POST['updated_at'] = $now;


      $newAllocsDetail = $postedAllocsJson ? getAllocsDetail( $app, $postedAllocsJson, true ) : null;
      debug_log( $newAllocsDetail, 'Allocations Detail: ', 2 );

      $totalCover = $newAllocsDetail ? $newAllocsDetail['totalCover'] : 0;
      if ( $amountCovered and $amountCovered != $totalCover ) throw new ValidationException(
        [ 'amount_covered' => 'Amount covered does not match the sum of allocations.' ] );

      $newAllocatedPins = $newAllocsDetail ? $newAllocsDetail['pins'] : []; // Linked TCCs by Pin Number!

      $newAllocAmounts = $newAllocsDetail ? $newAllocsDetail['pinAmounts'] : [];
      debug_log( $newAllocAmounts, 'Posted Alloc Amounts: ', 2 );

      if ( $newAllocAmounts ) {
        // Look for pin with pinNumber = '_SDA_' if the type = SDA
        if ( $type == 'SDA' and ! array_key_exists( '_SDA_', $newAllocAmounts ) ) throw new ValidationException(
          [ 'allocated_pins' => 'Please allocate an SDA pin or leave the field blank.' ] );

        if ( $type == 'SDA' and count( $newAllocAmounts ) > 1 ) throw new ValidationException(
          [ 'allocated_pins' => 'Only one pin can be allocated for SDA.' ] );

        if ( $type == 'SDA/FIA' and count( $newAllocAmounts ) != 2 ) throw new ValidationException(
          [ 'allocated_pins' => 'Please allocate both SDA and FIA pins or leave the field blank.' ] );

        // Check that there is no _SDA_ pin allocated
        if ( $type == 'FIA' and array_key_exists( '_SDA_', $newAllocAmounts ) ) throw new ValidationException(
          [ 'allocated_pins' => 'Please remove the SDA pin allocation.' ] );
      }


      // ----------
      // Save Trade
      // ----------

      $tradeModel = new TradeModel( $app );

      $app->db->pdo->beginTransaction();
      debug_log( 'Save Trade: DB Transaction started.', '', 2 );

      $tradeBefore = $tradeModel->getTradeById( $id );
      debug_log( $tradeBefore, 'getTradeById(), tradeBefore = ', 3 );

      if ( $isEdit )
      {
        $tccModel = new TccModel( $app );

        if ( ! $tradeBefore->id ) throw new Exception( "Trade $id not found." );

        $currentAllocsDetail = $tradeBefore ? getAllocsDetail( $app, $tradeBefore->allocated_pins ) : null;
        $currentAllocatedPins = $currentAllocsDetail ? $currentAllocsDetail['pins'] : [];
        $currentAllocPinAmounts = $currentAllocsDetail ? $currentAllocsDetail['pinAmounts'] : [];

        if ( $date != $tradeBefore->date or $type != $tradeBefore->sda_fia or $tradeId != $tradeBefore->trade_id ) {
          debug_log( 'Trade ID, Type or Date changed... Reset all related TCCs and trade allocations!', '', 2 );
          $currentPins = $currentAllocatedPins;
          unset( $currentPins['_SDA_'] );

          foreach( $currentPins as $pinNo => $pin ) { // Note: $pin === $tcc obj
            $tccModel->removeTradeFromAllocs( $pin, $tradeBefore );
          }

          $_POST['allocated_pins'] = null;
          $_POST['amount_covered'] = 0;
        }

        else if ( $postedAllocsJson != $tradeBefore->allocated_pins ) {
          debug_log( 'Allocated Pins JSON changed! Update related TCCs.', '',  2 );

          $allocsAdded = array_diff_key( $newAllocAmounts, $currentAllocPinAmounts );
          $allocsRemoved = array_diff_key( $currentAllocPinAmounts, $newAllocAmounts );
          $currentAllocsLessRemoved = array_diff_key( $currentAllocPinAmounts, $allocsRemoved );
          $allocsChanged = array_diff_assoc( $newAllocAmounts, $currentAllocsLessRemoved );
          $allocsToUpdate = array_merge( $allocsAdded, $allocsChanged );

          debug_log( $allocsAdded, 'pins added: ', 2 );
          debug_log( $allocsRemoved, 'pins removed: ', 2 );
          debug_log( $allocsChanged, 'pins changed', 2 );

          foreach( $allocsToUpdate as $tccPinNo => $coverAmount ) {
            debug_log( compact('tccPinNo', 'coverAmount'), 'Update PIN: ', 2 );
            $tcc = $newAllocatedPins[$tccPinNo] ?? null;
            debug_log( $tcc, 'Update TCC: ', 2 );
            if ( ! $tcc ) continue;
            $tccTrades = $tcc->allocated_trades ? json_decode( $tcc->allocated_trades, true ) : [];
            if ( ! is_array( $tccTrades ) ) $tccTrades = [];
            $currentUsed = floatval( $tccTrades[ $tradeId ] ?? 0 );
            $tcc->amount_used = floatval( $tcc->amount_used ) - $currentUsed + $coverAmount;
            $tcc->amount_remaining = floatval( $tcc->amount_remaining ) + $currentUsed - $coverAmount;
            $tcc->amount_available = $tcc->status == 'Approved' ? $tcc->amount_remaining : 0;
            $tccTrades[ $tradeId ] = $coverAmount;
            $tcc->allocated_trades = json_stringify( $tccTrades );
            $tccModel->save( (array) $tcc );
          }

          foreach( $allocsRemoved as $tccPinNo => $coverAmount ) {
            debug_log( compact('tccPinNo', 'coverAmount'), 'Remove PIN: ', 2 );
            $tcc = $currentAllocatedPins[$tccPinNo] ?? null;
            debug_log( $tcc, 'Remove TCC: ', 2 );
            if ( ! $tcc ) continue;
            $tccTrades = $tcc->allocated_trades ? json_decode( $tcc->allocated_trades, true ) : [];
            if ( ! is_array( $tccTrades ) ) $tccTrades = [];
            $currentUsed = floatval( $tccTrades[ $tradeId ] ?? 0 );
            $tcc->amount_used = floatval( $tcc->amount_used ) - $currentUsed + $coverAmount;
            $tcc->amount_remaining = floatval( $tcc->amount_remaining ) + $currentUsed - $coverAmount;
            $tcc->amount_available = $tcc->status == 'Approved' ? $tcc->amount_remaining : 0;
            unset( $tccTrades[ $tradeId ] );
            $tcc->allocated_trades = json_stringify( $tccTrades );
            $tccModel->save( (array) $tcc );
          }
        }
      } // isEdit


      $saveResult = $tradeModel->save( $_POST );
      if ( $isNew and ! $saveResult ) throw new Exception( 'Failed to save new Trade.' );
      debug_log( $saveResult, 'Save Trade apiResult: ', 2 );

      $savedTradeId = $saveResult ? $saveResult[ 'id' ] : null;

      if ( ! $savedTradeId )
        throw new Exception( 'Failed to save Trade. No ID returned after save.' );    


      // ---------------
      // Process Uploads
      // ---------------

      // This section needs to be AFTER saving!
      // Nothing here yet...


      // --------------------------
      // End Save Trade Transaction
      // --------------------------

      $app->db->pdo->commit();
      debug_log( 'Save Trade: DB Transaction committed.', '', 2 ); 

    } // try Save Trade

    catch ( ValidationException $ex ) {
      $errors = $ex->getErrors();
      debug_log( $errors, 'Validation Exception: ' );
      abort_uploads();
      json_response( [ 'success' => false, 'message' => $ex->getMessage(), 'errors' => $errors ] );
    }

    catch ( Exception $ex ) {
      $app->db->safeRollBack();
      $message = $ex->getMessage();
      if ( __DEBUG__ > 1 ) {
        $file = $ex->getFile();
        $line = $ex->getLine();
        $message .= "<br>---<br>Error on line: $line of $file";
      }
      $app->logger->log( $message, 'error' );
      abort_uploads();
      json_response( [ 'success' => false, 'message' => $message ] );
    }


    // -----------------
    // Update Remote: S2
    // -----------------

    try {

      debug_log( 'Update Remote: S2', '', 2 );

      $userEmail = 'neels@currencyhub.co.za';

      // Get the latest version of the Trade, as saved in the DB
      $trade = $app->db->getFirst( 'trades', $savedTradeId );
      debug_log( $trade, 'Saved Trade = ', 2 );

      if ( ! $trade ) throw new Exception( 'Trade not found.' );

      $tradeS2Mapper = new TradeS2MapperModel();

      $tradeHasRelevantChanges = (
        $tradeBefore->trade_id != $trade->trade_id ||
        $tradeBefore->sda_fia != $trade->sda_fia ||
        $tradeBefore->date != $trade->date ||
        $tradeBefore->zar_sent != $trade->zar_sent ||
        $tradeBefore->usd_bought != $trade->usd_bought
      );

      // Choose remote update extent...
      if ( $isNew or $tradeHasRelevantChanges ) {

        // First, update and calculate everything affected by this TCC locally
        $clientStateModel = new ClientStateModel( $app );
        $updateResult = $clientStateModel->updateStateFor( $client );
        debug_log( $updateResult, 'Client Update State Result: ', 3 );

        // Then, sync all the local changes with S2 (Google Sheets)
        // We exclude statement data to prevent generating a new statement.
        // TCC's don't affect a client's statement lines.
        $responseModel = new ClientS2ResponseModel( $app, [ 'no-statement' => true ] );
        $responseModel->add( 'tradeData', $tradeS2Mapper->mapTradeToRemoteRow( $trade ) );
        $payload = $responseModel->generate( $updateResult );

        run_google_script( 'submitTrade', $payload, $userEmail );

      } else {

        // Only Upsert Trade data, don't update calculated values or statements.
        // PS: Client side should check and prevent save if no changes were made
        $payload = [ 'sheetName' => __GOOGLE_TRADES_SHEET_NAME__, 'primaryKey' => 'Trade ID',
           'row' => $tradeS2Mapper->mapTradeToRemoteRow( $trade ) ];

        run_google_script( 'upsertRow', $payload, $userEmail );

      }

    } // try Update Remote

    catch ( Exception $ex ) {
      $app->db->safeRollBack();
      $message = $ex->getMessage();
      if ( __DEBUG__ > 1 ) {
        $file = $ex->getFile();
        $line = $ex->getLine();
        $message .= "<br>---<br>Error on line: $line of $file";
      }
      $app->logger->log( $message, 'error' );
      json_response( [ 'success' => false, 'message' => $message ] );
    }


    json_response( [ 'success' => true, 'id' => $savedTradeId, 'goto' => 'back' ] );

  } // saveTrade



  /** DEFAULT POST ACTION **/

  json_response( [ 'success' => false, 'message' => 'Invalid request' ] );  

}



// ---------
// -- GET --
// ---------

$form = new AppForm();

$tradeModel = new TradeModel( $app );
$trade = $tradeModel->getTradeById( $id );

if ( $isNew ) $trade->client_id = $_GET['client'] ?? '';

// for clients dropdown
$accountant = 'All';
$clientModel = new ClientModel( $app );
$clients = $clientModel->getAllByAccountant( $accountant );

$super = in_array( $app->user->role,  [ 'super-admin', 'sysadmin' ] );