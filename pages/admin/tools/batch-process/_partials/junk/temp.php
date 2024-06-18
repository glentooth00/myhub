<?php

$client->statement = $statement;
$client->total_net_profit = $totalNetProfit;
$client->total_net_return = round( $totalNetReturn, 2 );

$client->fia_mandate_remaining = $fiaMandateRemaining;
$client->sda_mandate_remaining = $sdaMandateRemaining;


$allocationMade = false;
$lastUpdatedTrade = null;

// Create a working copy of client.trades sorted by date (oldest first).
// Only add FIA trades that still require cover.
$fiaTradesThatNeedCover = array_filter( $clientTrades, function( $trade ) {
  return $trade->type === 'FIA' && $trade->pin_info->remaining > 0;
} );

$tradesThatNeedToBeSaved = [];

// Create a working copy of client.tccs sorted by date (oldest first).
$clientTccsSortedByDate = array_values( array_sort( $clientTCCs, function( $value ) {
  return $value->tcc_date;
} ) );

foreach ( $clientTccsSortedByDate as $tcc ) {

  debug_log( 'Allocate TCC_' . $tcc->tcc_pin . ' to ' . $client->name . '...' );

  $tccAllocated = $tcc->alloc_info;

  $tradeIndex = 0;

  while ( $tradeIndex < count( $fiaTradesThatNeedCover ) && $tccAllocated->remaining > 0 ) {

    $trade = $fiaTradesThatNeedCover[$tradeIndex];
    $tradeID = $trade->trade_id;
    $tradeAllocated = $trade->pin_info->allocated;

    debug_log( 'Trades that need cover: ' . ( count( $fiaTradesThatNeedCover ) - $tradeIndex ) );
    debug_log( 'TCC cover remaining: ' . $tccAllocated->remaining );
    debug_log( 'TCC status: ' . $tcc->status );

    // TCC can NOT cover trades that happened BEFORE it was issued!
    if ( $trade->date < $tcc->date ) {
      debug_log( 'Trade happened before TCC was issued, skipping...' );
      $tradeIndex++;
      continue;
    }


    // TCC can NOT cover trades that occurred AFTER it expired!
    if ( $trade->date >= $tcc->expiry_date ) {
      debug_log( 'Trade happened after TCC expired, skipping...' );
      $tradeIndex++;
      continue;
    }

    // If the trade has not been fully allocated, let's see if we can allocate some more...
    $amountToAllocate = min( $tccAllocated->remaining, $tradeAllocated->remaining );
    if ( $amountToAllocate > 0 ) // If we can allocate some more...
    {
      // Update the tccAllocated object
      $tccAllocated->allocated += $amountToAllocate;
      $tccAllocated->remaining -= $amountToAllocate;
      $tccAllocated->trades[] = [ 'id' => $tradeID, 'amount' => $amountToAllocate ];

      // Update the trade's tradeAllocatedPins object
      $tradeAllocated->covered += $amountToAllocate;
      $tradeAllocated->remaining -= $amountToAllocate;
      $tradeAllocated->pins[] = [ 'pin' => $tcc->tcc_pin, 'amount' => $amountToAllocate ];

      $lastUpdatedTrade = $trade;
      $allocationMade = true;

      debug_log( 'Allocated ' . $amountToAllocate . ' to ' . $tradeID . '...' );
    }

    // If the trade has been fully allocated, mark it for removal from fiaTradesThatNeedCover
    if ( $tradeAllocated->remaining <= 0 ) {
      // Mark trade for removal from fiaTradesThatNeedCover since it's now covered!
      $fiaTradesThatNeedCover[$tradeIndex] = null;
      // We changed the trade's status, so we need to save the changes.
      $tradesThatNeedToBeSaved[] = $trade;
      // Unset since it's now in "tradesThatNeedToBeSaved"
      $lastUpdatedTrade = null;
    }
    
    $tradeIndex++;

  } // while (tradeIndex < fiaTradesThatNeedCover.length && tccAllocated.remaining > 0)

  // Clean up the fiaTradesThatNeedCover array. Remove all the DELETED entries.
  $fiaTradesThatNeedCover = array_filter( $fiaTradesThatNeedCover, function( $trade ) {
    return $trade !== null;
  } );

  debug_log( 'Processed TCC_' . $tcc->tcc_pin . '... value = R' . $tccAllocated->allocated . 
    ', remaining = R' . $tccAllocated->remaining . ', expires ' . $tcc->expiry_date . '. ' . 
    ( $tcc->status === 'EXPIRED' ? 'EXPIRED' : 'Still ACTIVE' ) );

  debug_log( 'updateClients(), allocationMade: ' . ( $allocationMade ? 'Yes' : 'No' ) . 
    ', needsUpdate: ' . ( $tcc->needs_update ? 'Yes' : 'No' ) );

  // If an allocation was made in the trades loop above, we also need to save the
  // changes to thie current TCC.
  if ( $allocationMade || $tcc->needs_update ) {
    $tcc->amount_used = $tccAllocated->allocated;
    $tcc->amount_remaining = $tccAllocated->remaining;
    $expireTcc = $tcc->is_expired && $tcc->status !== 'Expired';
    if ( $expireTcc ) {
      $tcc->status = 'Expired';
      $tcc->expired = $year;
    }
    $tcc->updated_at = new DateTime();
    $tcc->updated_by = $app->user->id;
    $tcc->allocated_trades = json_encode( $tccAllocated->trades );
    $tcc->needs_update = false;
    // $tcc->save();
  }

} // foreach ( $clientTccsSortedByDate as $tcc )

// If the last updated trade is not null, it means that we updated it in the trades loop above,
// but did not fully allocate it, so it is not yet in the tradesThatNeedToBeSaved array.
if ( $lastUpdatedTrade ) $tradesThatNeedToBeSaved[] = $lastUpdatedTrade;

if ( count( $tradesThatNeedToBeSaved ) ) {

  debug_log( 'updateClients(), Save updated Trades to tradesMasterSheet...' );

  // Let's save the tradesThatNeedToBeSaved array to the tradesMasterSheet.
  // We have the tradesMasterSheet row number of each trade in the trade.rowNum property.
  foreach ( $tradesThatNeedToBeSaved as $trade ) {
    $allocatedPINsStr = json_encode( $trade->alloc_info );
    debug_log( 'updateClients(), Save updated Trade ' . $trade->trade_id );
  }

} // if ( tradesThatNeedToBeSaved.length )

// If we made any changes to the TCCs, we need to save them.
if ( $tccsThatNeedToBeSaved ) {
  debug_log( 'updateClients(), Save updated TCCs to tccsMasterSheet...' );
  foreach ( $tccsThatNeedToBeSaved as $tcc ) {
    debug_log( 'updateClients(), Save updated TCC_' . $tcc->tcc_pin );
  }
}


// -------------------------------------------------------------------------------

// Logger.log('updateClients(), Calculate FIA Approved, FIA Pending & FIA Declined...');

// let rollovers = 0;
// let rolloversAmount = 0;
// let newTccs = 0;
// let newTccsAmount = 0;
// let fiaApproved = 0;
// let fiaAvailable = 0;
// let fiaUnused = 0;
// client.tccs.forEach(tcc => {
//   const isRollover = tcc.tccRollover > 0;
//   // NB: NOT the same as fiaApprovedRemaining! fiaUnused don't care about expiries.
//   fiaUnused += tcc.tccAmountRemaining;
//   fiaAvailable += tcc.tccAmountAvailable;
//   // We assume that rollover amounts already have the amount initially reserved subtracted.
//   if (isRollover) { rollovers++; rolloversAmount += tcc.tccRollover; }
//   else { newTccs++; newTccsAmount += tcc.tccAmountCleared }
//   // NB: We don't count unused tcc allowance amounts or reserved amounts to make Dave's spreadsheet work.
//   fiaApproved += tcc.tccIsExpired ? tcc.tccAmountUsed : tcc.tccAmountClearedNet;
// });