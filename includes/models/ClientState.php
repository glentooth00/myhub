<?php namespace App\Models;

use stdClass;
use Exception;

use App\Models\Trade as TradeModel;
use App\Models\Client as ClientModel;
use App\Models\ClientStatement as ClientStatementModel;


class ClientState {

  private $app;


  public function __construct( $app )
  {
    $this->app = $app;
    debug_log( 'ClientState::__construct()', '', 2 );
  }


  public function unpackAllocationsJson( $allocsJsonString )
  {
    $allocs = $allocsJsonString ? json_decode( $allocsJsonString, true ) : [];
    if ( $allocs ) $allocs = array_combine( array_keys( $allocs ), 
        array_map( 'floatval', array_values( $allocs ) ) );
    return $allocs;
  }


  public function getCurrentState( $client )
  {
    $state = new stdClass();
    $state->sdaRemaining = $client->sda_mandate - $client->sda_used;
    $state->fiaRemaining = $client->fia_mandate - $client->fia_used;
    $state->fiaUnused = $client->fia_approved - $client->fia_used;
    $state->fiaAvailable = min( $state->fiaUnused, $state->fiaRemaining );
    return $state;
  }


  // Once we have Rollover on a PIN, we ignore history from the previous year and fully trust
  // the rollover value, since the history prior to rollover might be inconsistent or missing!
  // We also force the "amount used" to equal "pin net value - rollover value" if we decide to change the rollover value.
  // If we have any trades allocated after rollover, we need add their amounts to the "amount used".
  // We also need to update the "amount remaining" and "amount available" values accordingly.
  public function prepTcc( $tcc, $reset = false )
  {
    $app = $this->app;

    $tcc->updated = false;
    $tcc->time = strtotime( $tcc->date );
    $tcc->year = (int) date( 'Y', $tcc->time );
    $tcc->expiresAt = strtotime( ' + 1 year', $tcc->time );
    $tcc->amount_cleared = round( floatval( $tcc->amount_cleared ?? 0 ), 2 );
    $tcc->amount_reserved = round( floatval( $tcc->amount_reserved ?? 0 ), 2 );
    $tcc->amount_cleared_net = $tcc->amount_cleared - $tcc->amount_reserved;
    $tcc->rollover = round( floatval( $tcc->rollover ?? 0 ), 2 );


    // -----------------------
    // Reset prep (not normal)
    // -----------------------

    if ( $reset ) {

      // Not executed that often, so speed is not a huge concern.
      // Actioned by super users when they want a clean-up a client's TCC allocations.

      $tcc->allocs = [];
      $tcc->status = 'Approved';
      $tcc->sumOfAmountsUsed = 0;
      $tcc->amount_remaining = $tcc->rollover ? $tcc->rollover : $tcc->amount_cleared_net;
      $tcc->amount_available = $tcc->amount_remaining;
      $tcc->amount_used = $tcc->amount_cleared_net - $tcc->amount_remaining;

      // If this is a rollover TCC, put back pre-rollover trade allocations, since
      // we are only resetting allocations for the current year (i.e. AFTER rollover).

      if ( $tcc->rollover > 0 ) {

        $tccAllocs = $this->unpackAllocationsJson( $tcc->allocated_trades );

        $tradeModel = new TradeModel( $app );

        $coveredTradeUids = array_keys( $tccAllocs );
        debug_log( $coveredTradeUids, 'Covered Trade UIDs: ' );

        $coveredTrades = $tradeModel->getTradesByUid( $coveredTradeUids );
        debug_log( $coveredTrades, 'Covered Trades: ' );
        
        // While we have the covered trades fetched, let's also validate their allocations.
        // We don't care to validate trades covered in the current year, since we are
        // re-doing the allocations for all the trades in the current year anyway.

        // Since this is a rollover TCC, if a trade's year is the same as the TCC year, 
        // the trade must be a pre-rollover trade, so keep it's cover allocation.

        foreach ( $coveredTrades as $trade ) {
          $trade->year = (int) substr( $trade->date, 0, 4 );
          debug_log( 'Related Trade ' . $trade->trade_id . ' year: ' . $trade->year . ', TCC year: ' . $tcc->year );
          if ( $trade->year == $tcc->year ) { // i.e. Trade is pre-rollover
            $tradeAllocs = $this->unpackAllocationsJson( $trade->allocated_pins );
            $pins = array_keys( $tradeAllocs );
            if ( in_array( $tcc->tcc_pin, $pins ) ) {
              if ( $tradeAllocs[ $tcc->tcc_pin ] != $tccAllocs[ $trade->trade_id ] ) {
                debug_log( 'WARNING: Rollover TCC PIN ' . $tcc->tcc_pin . ' has and a different amount' .
                  ' allocated to trade ' . $trade->trade_id . ' than the trade itself has allocated to this PIN.' );
                throw new Exception ( 'Unable to succesfully update the FIA cover status for client ' . 
                  $tcc->client_id . '. Rollover TCC PIN ' . $tcc->tcc_pin . ' has too many inconsistencies. ' .
                  'Trade ' . $trade->trade_id . ' has ' . $tradeAllocs[ $tcc->tcc_pin ] .
                  ' allocated, while the TCC has ' . $tccAllocs[ $trade->trade_id ] . ' allocated.' );
              }
              $tcc->allocs[ $trade->trade_id ] = $tccAllocs[ $trade->trade_id ];
              $tcc->sumOfAmountsUsed += $tccAllocs[ $trade->trade_id ];
            } else {
              $error = 'Rollover TCC PIN ' . $tcc->tcc_pin . ' has cover allocated to trade ' . 
                $trade->trade_id . ', but the trade itself doesn\'t know about it!';
              debug_log( $error, 'WARNING: ' );
              throw new Exception ( 'Unable to succesfully update the FIA cover status for client ' . 
                $tcc->client_id . '. ' . $error );             
            }
          } 
        } // foreach ( $coveredTrades )

      } // if ( $tcc->rollover > 0 )

      $tcc->allocated_trades = null;
      $tcc->expired = null;
      $tcc->updated = true;

    } // end: if ( prepTcc::reset )


    // -----------------------
    // Normal prep (not reset)
    // -----------------------

    else {

      $tcc->allocs = $this->unpackAllocationsJson( $tcc->allocated_trades );
          
      // Amount used sanity check
      $tcc->amount_used = round( floatval( $tcc->amount_used ?? 0 ), 2 );
      $tcc->sumOfAmountsUsed = array_sum( $tcc->allocs );

      if ( $tcc->amount_used != $tcc->sumOfAmountsUsed ) {

        debug_log( 'WARNING: TCC PIN ' . $tcc->tcc_pin . ' has a different amount used' .
          ' than the sum of its allocated trade amounts.' );

        debug_log( $tcc->amount_remaining, 'TCC amount remaining = ', 2 );

        if ( $tcc->rollover > 0 ) {

          debug_log( $tcc->rollover, 'We also have rollover. TCC rollover = ', 2 );
          
          // Let's validate amount used and remaining ...

          $tradeModel = new TradeModel( $app );

          $coveredTradeUids = array_keys( $tcc->allocs );

          $coveredTrades = $tradeModel->getTradesByUid( $coveredTradeUids );

          $coverAfterRolloverCheck = 0;
          foreach ( $coveredTrades as $trade ) {
            // While we have the trade fetched, let's check if the allocations are consistent.
            $tradeAllocs = $this->unpackAllocationsJson( $trade->allocated_pins );
            $pins = array_keys( $tradeAllocs );
            if ( in_array( $tcc->tcc_pin, $pins ) ) {
              if ( $tradeAllocs[ $tcc->tcc_pin ] != $tcc->allocs[ $trade->trade_id ] ) {
                throw new Exception ( 'Unable to update the FIA cover status for client ' . 
                  $tcc->client_id . '. TCC PIN ' . $tcc->tcc_pin . ' has  ' . $tcc->allocs[ $trade->trade_id ] .
                ' allocated to trade ' . $trade->trade_id . ', but the trade has a different amount ' . 
                $tradeAllocs[ $tcc->tcc_pin ] . ' allocated to the PIN.' );
              }
            } else {
              debug_log( 'WARNING: Rollover TCC PIN ' . $tcc->tcc_pin . ' has cover allocated to trade ' . 
                $trade->trade_id . ', but the trade itself doesn\'t know about it!' );
            }
            $trade->year = (int) substr( $trade->date, 0, 4 );
            if ( $trade->year > $tcc->year ) { $coverAfterRolloverCheck += $tcc->allocs[ $trade->trade_id ]; }
          } // foreach ( $coveredTrades )

          // If we can trust allocations AFTER rollover, we can fix invalid amounts used and remaining.
          $coverAfterRollover = $tcc->rollover - $tcc->amount_remaining;
          if ( $coverAfterRolloverCheck == $coverAfterRollover ) {
            // Let's see if we can trust the "amount remaining"... If not, re-calculate it and the amount used.
            $calculatedRemaining = $tcc->amount_cleared_net - $tcc->amount_used;
            if ( $tcc->amount_remaining != $calculatedRemaining ) {
              debug_log( 'WARNING: Fixing TCC PIN ' . $tcc->tcc_pin . 'amount used and remaining.' .
                ' Expected ' . $tcc->amount_remaining . ' remaining, but found ' . $calculatedRemaining );
              // Reset TCC amount used and remaining...
              $tcc->amount_used = $tcc->rollover + $coverAfterRollover;
              $tcc->amount_remaining = $tcc->amount_cleared_net - $tcc->amount_used;
              debug_log( 'Fixed amount used = ' . $tcc->amount_used . ', remaining = ' . $tcc->amount_remaining );
              $tcc->updated = true;
            }
          }
          else {
            throw new Exception ( 'Unable to update the FIA cover status for client ' . 
              $tcc->client_id . '. The sum of cover used AFTER rollover on PIN ' . 
              $tcc->tcc_pin . ' does not add up! Expected ' . $coverAfterRollover . 
              ' rollover used, but found ' . $coverAfterRolloverCheck . '.' );
          }

        } // if ( $tcc->rollover > 0 )

        else {
          debug_log( 'WARNING: Found inconsistent FIA cover on client ' . $tcc->client_id . '. The cover used' .
          ' on TCC PIN ' . $tcc->tcc_pin . ' does not add up! Expected ' . $tcc->amount_used . ' used,' .
          ' but found ' . $tcc->sumOfAmountsUsed );
          // Reset TCC amount used and remaining...
          $tcc->amount_used = $tcc->sumOfAmountsUsed;
          $tcc->amount_remaining = $tcc->amount_cleared_net - $tcc->amount_used;
          debug_log( 'Fixed amount used = ' . $tcc->amount_used . ', remaining = ' . $tcc->amount_remaining );
          $tcc->updated = true;          
        }

      } // if ( $tcc->amount_used != $tcc->sumOfAmountsUsed )

      // Expire old pins
      if ( $tcc->status !== 'Expired' and time() > $tcc->expiresAt ) {
        debug_log( 'ClientState::prepTcc(), Expire old PIN: ' . $tcc->tcc_pin );
        $tcc->expired = (int) date( 'Y', $tcc->year + 1 ); // Expired In
        $tcc->amount_available = 0;
        $tcc->status = 'Expired';
        $tcc->updated = true;
      }
      // "Soft" Expire fully used pins
      elseif ( $tcc->status == 'Approved' and $tcc->amount_used >= $tcc->amount_cleared_net and ! $tcc->expired ) {
        debug_log( 'ClientState::prepTcc(), Soft Expire fully used PIN: ' . $tcc->tcc_pin );
        $tcc->expired = min( (int) date( 'Y' ), $tcc->year + 1 );
        $tcc->amount_available = 0;
        $tcc->updated = true;
      }
      // Default "remaining" values if they are null. i.e. never been set.
      // I.e. Fix bad TCCs that have no remaining values.
      // TODO: Should check for more bad variants + Also fix them in the DB.
      if ( ! $tcc->amount_used and ! $tcc->amount_remaining ) {
        $tcc->amount_remaining = $tcc->amount_cleared_net;
        $tcc->amount_available = $tcc->status == 'Approved' ? $tcc->amount_cleared_net : 0;
        $tcc->updated = true;
      } else {
        $tcc->amount_available = round( floatval( $tcc->amount_available ?? 0 ), 2 );
        $tcc->amount_remaining = round( floatval( $tcc->amount_remaining ?? 0 ), 2 );
      }

    } // end: if (prepTcc::not-reset) i.e. Normal update

    return $tcc;
  }


  public function prepTrade( $trade, $reset = false )
  {
    $trade->time = strtotime( $trade->date );
    $trade->year = date( 'Y', $trade->time );
    $trade->zar_sent = round( floatval( $trade->zar_sent ?? 0 ), 2 );
    $trade->amount_covered = $reset ? 0 : round( floatval( $trade->amount_covered ?? 0 ), 2 );
    $trade->allocated_pins = $reset ? null : $trade->allocated_pins;
    $trade->pins = $this->unpackAllocationsJson( $trade->allocated_pins );
    $trade->sumOfPinsAllocated = $reset ? 0 : array_sum( $trade->pins );
    $trade->sdaCoverAllocated = $reset ? 0 : ( $trade->pins['_SDA_'] ?? 0 );
    $trade->fiaCoverAllocated = $reset ? 0 : ( $trade->sumOfPinsAllocated - $trade->sdaCoverAllocated );
    if ( $trade->amount_covered != $trade->sumOfPinsAllocated ) {
      debug_log( 'WARNING: Trade ' . $trade->trade_id . ' has a different amount covered' .
        ' than the sum of its allocated PIN amounts.' );
      throw new Exception ( 'Unable to succesfully update the FIA cover status for client ' . 
        $trade->client_id . '. Trade ' . $trade->trade_id . ' has too many inconsistencies.' );
    }    
    $trade->cover_required = $trade->zar_sent - $trade->amount_covered;
    $trade->updated = $reset;
    return $trade;
  }


  /**
   * @param $client mixed e.g 'neelsdev' or {clientObject}
   * @param $options array e.g. [ 'year' => 2024, 'redoAllocations' => true, 'setRollovers' => true ]
   * @return array [ $statement, $client, $trades, $tccs ]
   */
  public function updateStateFor( $client, $options = [] )
  {
    debug_log( PHP_EOL, '', 2 );
    debug_log( $client, 'ClientState::updateStateFor(), client = ', 4 );
    debug_log( $options, 'ClientState::updateStateFor(), options = ', 2 );

    $redoAllocs = $options['redoAllocations'] ?? false;
    $setRollovers = $options['setRollovers'] ?? false;

    $app = $this->app;

    $now = date( 'Y-m-d H:i:s' );

    $userUID = $app->user->user_id;

    $theYear = isset( $options['year'] ) ? $options['year'] : date( 'Y' );
    $thePreviousYear = $theYear - 1;

    $clientModel = new ClientModel( $app );


    // -----------------
    // --- Statement ---
    // -----------------

    // Have a look at ClientStatementModel...
    // It does some of the heavy lifting for us.
    $statement = new ClientStatementModel( $app );
    $statement->generate( $client, [ 'year' => $theYear ] );

    // $client can be a UID string, so we use the 
    // statement model's already resolved client object.
    $client = $statement->getData( 'client' );

    // Some client information differ from year to year (like mandates), 
    // so we need to get the most relevant info for the year we're working on.
    // Conveniently, the statement model already does this for us.
    $annualInfo = $statement->getData('annualInfo');

    debug_log( PHP_EOL );
    debug_log( '***************************************************' );
    debug_log( "Processing: $client->name [$client->client_id]" );
    debug_log( '***************************************************' );

    debug_log( PHP_EOL, '', 2 );
    debug_log( $client->sda_used, 'SDA Used: ', 2 );
    debug_log( $client->fia_used, 'FIA Used: ', 2 );

    debug_log( PHP_EOL, '', 2 );
    debug_log( $statement->getData( 'fiaMandateRemaining' ), 'FIA Mandate Remaining: ', 2 );
    debug_log( $statement->getData( 'sdaMandateRemaining' ), 'SDA Mandate Remaining: ', 2 );

    debug_log( PHP_EOL, '', 3 );
    debug_log( $statement->toCsv(), 'Statement Lines (CSV): ' . PHP_EOL, 3 );

    debug_log( PHP_EOL, '', 2 );
    debug_log( $statement->getData( 'totalNetProfit' ), 'Total Net Profit: ', 2 );
    debug_log( round( $statement->getData( 'totalNetReturn' ), 2 ), 'Total Net Return: ', 2 );


    // -----------------
    // --- SDA / FIA ---
    // -----------------

    debug_log( PHP_EOL, '', 2 );
    debug_log( $redoAllocs ? 'Y' : 'N', 'redoAllocs = ', 2 );

    $tradesForTheYear = $statement->getData( 'lines' );
    $tccsAffectingState = $clientModel->getClientTccPins( $client, ['type' => 'AffectsState', 'year' => $theYear] );

    // Prep Trades
    $prepedTrades = [];
    foreach ( $tradesForTheYear as $key => $trade ) {
      $prepedTrades[$key] = $this->prepTrade( $trade, $redoAllocs );
    }

    // Prep TCCs
    $prepedTccsAffectingState = [];
    foreach ( $tccsAffectingState as $key => $tcc ) {
      $prepedTccsAffectingState[$key] = $this->prepTcc( $tcc, $redoAllocs );
    }

    debug_log( PHP_EOL, '', 2 );
    debug_log( count( $prepedTrades ), 'Preped Trades = ', 2 );
    debug_log( count( $prepedTccsAffectingState ), 'Preped TCCs Affecting State = ', 2 );
    debug_log( PHP_EOL, '', 2 );

    // Cover already allocated.
    $sumOfSdaAllocated = 0;
    $sumOfFiaAllocated = 0;
    foreach ( $prepedTrades as $prepedTrade ) {
      $sumOfSdaAllocated += $prepedTrade->sdaCoverAllocated;
      $sumOfFiaAllocated += $prepedTrade->fiaCoverAllocated;
    }

    // Client mandates.
    $sdaMandate = $annualInfo->sda_mandate;
    $fiaMandate = $annualInfo->fia_mandate;

    // Cover remaining.
    $sdaRemainingBeforeUpdate = $sdaMandate - $sumOfSdaAllocated;
    $fiaRemainingBeforeUpdate = $fiaMandate - $sumOfFiaAllocated;
    $sdaRemaining = $sdaRemainingBeforeUpdate;
    $fiaRemaining = $fiaRemainingBeforeUpdate;


    // Trades that need cover.
    $tradesThatNeedCover = [];
    foreach ( $prepedTrades as $prepedTrade ) {
      if ( $prepedTrade->amount_covered < $prepedTrade->zar_sent ) $tradesThatNeedCover[] = $prepedTrade;
    }

    // Create a working clone of the trades list.
    $tradesNotFullyCovered = $tradesThatNeedCover;

    // Discount current usage if we're redoing allocations.
    if ( $redoAllocs ) {
      $client->sda_used = 0;
      $client->fia_used = 0;
    }

    // Sanity check.
    if ( $sumOfSdaAllocated != $client->sda_used ) {
      $app->logger->log( 'Sum of allocated SDA cover does not match client SDA used! Did you edit allocations?', 'warning' );
      $client->sda_used = $sumOfSdaAllocated;
    }

    // Sanity check.
    if ( $sumOfFiaAllocated != $client->fia_used ) {
      $app->logger->log( 'Sum of allocated FIA cover does not match client FIA used! Did you edit allocations?', 'warning' );
      $client->fia_used = $sumOfFiaAllocated;
    }


    // --------------------
    // --- Allocate SDA ---
    // --------------------

    debug_log( PHP_EOL );
    debug_log( '**********************************' );
    debug_log( "Allocate SDA: [$client->client_id]" );
    debug_log( '**********************************' );

    debug_log( PHP_EOL, '', 2 );
    debug_log( 'Allocate SDA (Standard Foreign Investment Allowance: Max 1Mil) cover first:', '', 2 );
    debug_log( '---', '', 2 );
    debug_log( $sdaMandate, 'Client SDA Mandate: ', 2 );
    debug_log( $sdaRemainingBeforeUpdate, 'Client SDA Remaining Before Update: ', 2 );
    debug_log( '---', '', 2 );

    foreach ( $tradesNotFullyCovered as $trade ) {

      if ( $sdaRemaining <= 0 ) break; // No more SDA cover available

      if ( $trade->sda_fia == 'FIA') continue; // Skip FIA only trades

      debug_log( $trade, 'Allocate SDA for: ', 4 );

      // NOTE: We should not cover SDA trades from other years, SDA does not roll over!
      if ( $trade->year != $theYear ) continue;    

      debug_log( 'Processing trade_'  . $trade->trade_id . ' (SDA Round), ' . $trade->date . ', type = ' . 
        $trade->sda_fia . ', needs ' . $trade->cover_required . ' cover, ' . $sdaRemaining . 
        ' available, allocated_pins = ' . $trade->allocated_pins . ', update year = ' . 
        $theYear, '', 2 );

      $coverToAllocate = min( $trade->cover_required, $sdaRemaining );

      $trade->pins['_SDA_'] = round( $trade->sdaCoverAllocated + $coverToAllocate, 2 );

      // Update the trade's allocations
      $trade->allocated_pins = json_stringify( $trade->pins );

      // Update the amount covered for this trade
      $trade->amount_covered += $coverToAllocate;
      $trade->cover_required -= $coverToAllocate;

      // Tag the trade as updated
      $trade->updated = true;

      // Update the amount used for this client
      $client->sda_used += $coverToAllocate;
      $sdaRemaining -= $coverToAllocate;

    } // END: foreach ( $tradesNotFullyCovered as $trade ) - SDA Round

    // Remove fully covered trades from the tradesNotFullyCovered array.
    $tradesNotFullyCovered = array_filter( $tradesNotFullyCovered, function( $trade ) {
      $fullyCovered = $trade->zar_sent - $trade->amount_covered <= 0;
      return !$fullyCovered;
    } );


    // --------------------
    // --- Allocate FIA --- 
    // --------------------

    debug_log( PHP_EOL );
    debug_log( PHP_EOL );
    debug_log( '**********************************' );
    debug_log( "Allocate FIA: [$client->client_id]" );
    debug_log( '**********************************' );

    debug_log( PHP_EOL, '', 2 );
    debug_log( 'Allocate FIA (Special Extended Foreign Investment Allowance: Max 10Mil) cover next:', '', 2 );
    debug_log( '---', '', 2 );
    debug_log( $fiaMandate, 'Client FIA Mandate: ', 2 );
    debug_log( $fiaRemainingBeforeUpdate, 'Client FIA Remaining Before Update: ', 2 );
    debug_log( '---', '', 2 );

    debug_log( PHP_EOL, '', 2 );
    debug_log( count( $prepedTccsAffectingState ), 'Cycle through all TCCs affecting client state: ', 2 );
    foreach ( $prepedTccsAffectingState as $tcc ) {

      if ( ! $tradesNotFullyCovered ) {
        debug_log( PHP_EOL );
        debug_log( 'STOP FIA Allocations. No more trades that need cover.', '', 2 );
        break;
      }

      if ( $fiaRemaining <= 0 ) {
        debug_log( PHP_EOL );
        debug_log( 'STOP FIA Allocations. No more FIA remaining.', '', 2 );
        break;
      }

      debug_log( PHP_EOL );
      debug_log( 'Try using pin: ' . $tcc->tcc_pin . ', ' . $tcc->date . ', val: ' . $tcc->amount_cleared .
        ', net: ' . $tcc->amount_cleared_net . ', used: ' . $tcc->amount_used . 
        ', ununsed: ' . $tcc->amount_remaining . ', avail: ' . $tcc->amount_available . 
        ', trades = ' . $tcc->allocated_trades, '', 2 );

      debug_log( '~~~', '', 2 );
      debug_log( count( $tradesNotFullyCovered ), 'Cycle through trades not fully covered: ', 2 );

      // See if we can cover any needy trades with this TCC
      foreach ( $tradesNotFullyCovered as $trade ) {

        if ( $fiaRemaining <= 0 ) break ; // No more FIA cover available

        // NOTE: We rely on and trust the "amount available" value... make sure it's correct!
        if ( $tcc->amount_available <= 0 ) break; // No more TCC cover available

        if ( $trade->sda_fia == 'SDA') continue; // Skip SDA only trades
        if ( ! $trade->cover_required ) continue;
        if ( $trade->time < $tcc->time ) continue;
        if ( $trade->time > $tcc->expiresAt ) continue;        

        debug_log( 'Processing trade_'  . $trade->trade_id . ' (FIA Round), ' . $trade->date . ', type = ' . 
          $trade->sda_fia . ', needs ' . $trade->cover_required . ' cover, ' .  $fiaRemaining . ' fia_remaining,' . 
          ' PIN ' . $tcc->tcc_pin . ' has ' . $tcc->amount_available . ' available, pins = ' . 
          $trade->allocated_pins . ', update year = ' . $theYear, '', 2 );        

        // Validate existing allocations:

        // Get the trade and tcc pair amounts with respect to each other.
        $tradeCurrentCover = $trade->pins[ $tcc->tcc_pin ] ?? 0;
        $tccCurrentUsedOnTrade = $tcc->allocs[ $trade->trade_id ] ?? 0;

        // Check if the trade and related TCC have different allocated amounts.
        if ( $tradeCurrentCover != $tccCurrentUsedOnTrade ) {
          // Remove the current cover from both the TCC and trade because they do not match.
          debug_log( 'WARNING: Allocations on trade ' . $trade->trade_id . ' and related TCC ' . 
            $tcc->tcc_pin . ' do not match!' );
          debug_log( 'Removing previous allocations if possible, because they do not match.' );
  
          if ( $tradeCurrentCover ) {
            $trade->amount_covered = $trade->amount_covered - $tradeCurrentCover;
            $trade->cover_required += $tradeCurrentCover;
            unset( $trade->pins[ $tcc->tcc_pin ] );
            $tradeCurrentCover = 0;
          }

          if ( $tccCurrentUsedOnTrade ) {
            $tcc->amount_used -= $tccCurrentUsedOnTrade;
            $tcc->amount_remaining += $tccCurrentUsedOnTrade;
            $tcc->amount_available += $tccCurrentUsedOnTrade;
            unset( $tcc->allocs[ $trade->trade_id ] );
            $tccCurrentUsedOnTrade = 0;
          }
        }

        $coverToAllocate = min( $trade->cover_required, $tcc->amount_available, $fiaRemaining );
        // debug_log( 'TCC cover to allocate: ' . $coverToAllocate );

        // Update the TCC.
        $tcc->amount_used += $coverToAllocate;
        $tcc->amount_remaining -= $coverToAllocate;
        $tcc->amount_available -= $coverToAllocate;
        $tcc->allocs[ $trade->trade_id ] = $tccCurrentUsedOnTrade + $coverToAllocate;
         // Expire the TCC if we're redoing allocations and it's a rollover.
        if ( $redoAllocs and $tcc->year < $theYear ) {
          $tcc->amount_used = $tcc->amount_cleared_net - $tcc->amount_remaining;
          $tcc->amount_available = 0;
          $tcc->expired = $theYear;
          $tcc->status = 'Expired';
        }
        // Else "Soft" expire the TCC if it's fully used.
        else if ( $tcc->amount_remaining <= 0 ) {
          $tcc->amount_remaining = 0;
          $tcc->amount_available = 0;
          $tcc->amount_used = $tcc->amount_cleared_net;
          $tcc->expired = min( $theYear, $tcc->year + 1 );
        }
        $tcc->updated = true;

        // Update the Trade.
        $trade->amount_covered += $coverToAllocate;
        $trade->cover_required -= $coverToAllocate;
        $trade->pins[ $tcc->tcc_pin ] = $tradeCurrentCover + $coverToAllocate;
        $trade->allocated_pins = json_stringify( $trade->pins );
        $trade->updated = true;

        // Update the Client state.
        $client->fia_used += $coverToAllocate;
        $fiaRemaining -= $coverToAllocate;

      } // foreach ( $tradesNotFullyCovered as $trade )

      debug_log( '~~~', '', 2 );
 
      $tcc->allocated_trades = json_stringify( $tcc->allocs );
      debug_log( 'Done allocating pin: ' . $tcc->tcc_pin . ', used: ' . $tcc->amount_used .
          ', unused: ' . $tcc->amount_remaining . ', avail: ' . $tcc->amount_available .
          ', trades = ' . $tcc->allocated_trades, '', 2 );

      // Remove fully covered trades from the tradesNotFullyCovered array.
      $tradesNotFullyCovered = array_filter( $tradesNotFullyCovered, function( $trade ) {
        $fullyCovered = $trade->zar_sent - $trade->amount_covered <= 0;
        return !$fullyCovered;
      } );    

    } // foreach ( $prepedTccsAffectingState as $tcc )


    // -------------------------------
    // --- Save Allocation Changes ---
    // -------------------------------

    $updateOptions = [ 'autoStamp' => true, 'user' => $userUID ];

    // Save updated trades.
    $updatedTrades = array_filter( $tradesThatNeedCover, function( $trade ) {
      return $trade->updated;
    } );

    debug_log( PHP_EOL, '', 2 );
    debug_log( PHP_EOL, '', 2 );
    debug_log( '---', '', 2 );
    debug_log( count( $updatedTrades ), 'Save all updated trades: ', 2 );
    debug_log( '---', '', 2 );

    foreach ( $updatedTrades as $trade ) {
      debug_log( 'Save trade_' . $trade->trade_id . ', value: ' . $trade->zar_sent . 
        ', covered: ' . $trade->amount_covered . ', needs: ' . $trade->cover_required .
        ', pins: ' . $trade->allocated_pins, '', 2 );

      $app->db->table( 'trades' )->update( (array) $trade, $updateOptions );
    }

    // Save updated TCCs.
    $updatedTccs = array_filter( $prepedTccsAffectingState, function( $tcc ) {
      return $tcc->updated;
    } );

    debug_log( PHP_EOL, '', 2 );
    debug_log( '---', '', 2 );
    debug_log( count( $updatedTccs ), 'Save all updated TCCs: ', 2 );
    debug_log( '---', '', 2 );

    // NOTE: Some TCCs are "auto expired" when we run prepTcc() above and
    // marked as "updated" even though nothing changed during allocations.
    foreach ( $updatedTccs as $tcc ) {
      debug_log( 'Save updated PIN ' . $tcc->tcc_pin . ', ' . $tcc->date . ', ' . $tcc->status . 
        ', ' . $tcc->amount_cleared . ', net: ' . $tcc->amount_cleared_net . 
        ', used: ' . $tcc->amount_used . ', unused: ' . $tcc->amount_remaining . 
        ', avail: ' . $tcc->amount_available . ', expired: ' . $tcc->expired . 
        ', trades: ' . $tcc->allocated_trades, '', 2 );

      $tcc->updated_at = $now;
      $tcc->updated_by = $userUID;
      $tcc->sync_at = $now;
      $tcc->sync_by = $userUID;
      $tcc->sync_from = 'local';
      $tcc->sync_type = 'update';

      $app->db->table( 'tccs' )->update( (array) $tcc, $updateOptions );
    }


    // ---------------------
    // --- Update Client --- 
    // ---------------------

    debug_log( PHP_EOL, '', 2 );
    debug_log( PHP_EOL, '', 2 );
    debug_log( '---', '', 2 );
    debug_log( count( $prepedTccsAffectingState ), 'Calculate Total FIA Applicable to this year\'s trades ATM. Pins in play: ', 2 );
    debug_log( '---', '', 2 );

    // S2 Sheet
    // ---
    // FIA Approved = QUERY('FIA TAX Clearances'!A1:W,"select * where (C = 'Approved' AND YEAR(E) = YEAR(NOW())) OR (H > 0 AND YEAR(E) = YEAR(NOW()) - 1)", 1)
    // ---
    // client.tccs.forEach(tcc => {
    //   const isRollover = tcc.rollover > 0;
    //   const rolloverUsed = isRollover ? tcc.rollover - tcc.amountRemaining : 0;
    //   fiaApproved += isRollover ? ( tcc.isExpired ? rolloverUsed : tcc.rollover ) : tcc.amountClearedNet;
    // });

    // S2 App Sheet
    // ---
    // Approved FIA TAX Clearances = 
    //   OR(AND([Status] = "Approved", YEAR([Date]) = YEAR(NOW())), AND([Rollover] > 0, YEAR([Date]) = YEAR(NOW())-1))
    // ---
    // FIA Approved =
    //   SUM([Related FIA Applicable ATM][Rollover]) -
    //   SUM(SELECT([Related FIA Applicable ATM][Amount Remaining], [Status] = "Expired")) +
    //   SUM(SELECT([Related FIA Applicable ATM][Amount Cleared Net], YEAR([Date]) = YEAR(NOW())))

    // S3 App
    // ---
    // $tccsAffectingState = ( status = "Approved" AND YEAR(`date`) = $theYear ) OR ( rollover > 0 AND YEAR(`date`) = $lastYear )
    // $totalFiaApplicableToThisYearsTradesAtm = $totalApprovedFiaThisYear + $totalApprovedFiaFromLastYear + $totalExpiredFiaFromLastYearUsedThisYear;
    // ---

    // WARNING: If you're new here... You might think you know why we do this, but you don't.
    $totalFiaApplicableToThisYearsTradesAtm = 0;
    foreach ( $prepedTccsAffectingState as $tcc ) {
      $isRollover = $tcc->rollover > 0;
      $isExpired = $tcc->status == 'Expired';
      $totalFiaApplicableToThisYearsTradesAtm += $isRollover
        ? ( $isExpired ? $tcc->rollover - $tcc->amount_remaining : $tcc->rollover )
        : $tcc->amount_cleared_net;
    }

    debug_log( $totalFiaApplicableToThisYearsTradesAtm, 'totalFiaApplicableToThisYearsTradesAtm = ', 2 );

    $totalFiaPending = 0;
    $clientPendingTCCs = $clientModel->getPendingTccs( $client->client_id );
    foreach ( $clientPendingTCCs as $tcc ) { $totalFiaPending += $tcc->amount_cleared_net; }

    $totalFiaDeclined = 0;
    $clientDeclinedTCCs = $clientModel->getDeclinedTccs( $client->client_id, $theYear );
    foreach ( $clientDeclinedTCCs as $tcc ) { $totalFiaDeclined += $tcc->amount_cleared_net; }       

    // Save new Client state.
    $updatedClientData = [
      'id' => $client->id,
      'fia_approved' => $totalFiaApplicableToThisYearsTradesAtm,
      'sda_used' => $statement->getData( 'totalSDA' ), // We use STATEMENT (allocations agnostic) totals.
      'fia_used' => $statement->getData( 'totalFIA' ),
      'fia_pending' => $totalFiaPending,
      'fia_declined' => $totalFiaDeclined,
      'last_action' => 'Update SDA/FIA Values',
      'action_at' => $now,
      'updated_at' => $now,
      'updated_by' => $userUID,
      'sync_at' => $now,
      'sync_by' => $userUID,
      'sync_from' => 'local',
      'sync_type' => 'update',
    ];

    debug_log( PHP_EOL, '', 2 );
    debug_log( PHP_EOL, '', 2 );
    debug_log( $updatedClientData, 'Update client state: ', 2 );

    $app->db->table( 'clients' )->update( $updatedClientData );


    // --------------------
    // --- Sanity Check --- 
    // --------------------

    $sdaMandateRemaining = $statement->getData( 'sdaMandateRemaining' );
    $fiaMandateRemaining = $statement->getData( 'fiaMandateRemaining' );

    if ( $sdaRemaining != $sdaMandateRemaining ) {
      // $sdaRemaining should have been updated when we allocated cover to trades
      // and should now be the same as $sdaMandateRemaining.
      $app->logger->log( 'CalculatedSdaRemaining != Statement->SdaMandateRemaining after allocations!', 'warning' );
    }

    if ( $fiaRemaining != $fiaMandateRemaining ) {
      // See above.
      $app->logger->log( 'CalculatedFiaRemaining != Statement->FiaMandateRemaining after allocations!', 'warning' );
    }


    // ----------------------
    // --- Roll Over TCCs --- 
    // ----------------------

    // Only rollover TCCs in an update year, if the update year is NOT the current year.
    // This needs to be AFTER the "FIA Approved ATM" calculation above.
    if ( $theYear <> date( 'Y' ) or $setRollovers )
    {
      debug_log( PHP_EOL, '', 2 );
      debug_log( PHP_EOL, '', 2 );
      debug_log( '---', '', 2 );
      debug_log( count( $prepedTccsAffectingState ), 'Rollover PINS! Rollover candidates: ', 2 );
      debug_log( '---', '', 2 );

      $rolloverTccs = [];
      foreach ( $prepedTccsAffectingState as $tcc ) {
        debug_log( $tcc->tcc_pin, 'Check pin: ', 2 );
        if ( $tcc->year == $theYear and $tcc->amount_available ) {
          $tcc->rollover = $tcc->amount_available;
          $rolloverTccs[] = $tcc;
        }
      }

      debug_log( PHP_EOL, '', 2 );
      debug_log( count( $rolloverTccs ), 'Save rollover pins: ', 2 );

      foreach ( $rolloverTccs as $tcc ) {
        debug_log( 'Save rollover PIN ' . $tcc->tcc_pin . ', ' . $tcc->date . ', ' . $tcc->status . 
          ', ' . $tcc->amount_cleared . ', net: ' . $tcc->amount_cleared_net . 
          ', used: ' . $tcc->amount_used . ', unused: ' . $tcc->amount_remaining . 
          ', avail: ' . $tcc->amount_available . ', expired: ' . $tcc->expired . 
          ', trades: ' . $tcc->allocated_trades, '', 2 );
        $app->db->table( 'tccs' )->update( (array) $tcc, $updateOptions );
      }
    }


    // ----------------------
    // --- Export Results --- 
    // ----------------------

    $fiaUnused = $totalFiaApplicableToThisYearsTradesAtm - $statement->getData( 'totalFIA' );
    $fiaAvailable = min( $fiaUnused, $fiaMandateRemaining );    

    $updatedClientData['fia_mandate'] = $client->fia_mandate;
    $updatedClientData['sda_mandate'] = $client->sda_mandate;
    $updatedClientData['sda_mandate_remaining'] = $sdaMandateRemaining;
    $updatedClientData['fia_mandate_remaining'] = $fiaMandateRemaining;
    $updatedClientData['fia_available'] = $fiaAvailable;
    $updatedClientData['fia_unused'] = $fiaUnused;

    return [
      'statement' => $statement,
      'client' => $updatedClientData,
      'trades' => $updatedTrades,
      'tccs' => $updatedTccs,
    ];

  } // updateStateFor  


} // ClientState