<?php namespace App\Models;

use stdClass;
use Exception;


class TccS2Mapper {

  public $columnsMap = [
    'tcc_id' => 'TCC ID',
    'client_id' => 'Client ID',
    'status' => 'Status',
    'application_date' => 'Application Date',
    'date' => 'Date',
    'amount_cleared' => 'Amount Cleared',
    'amount_reserved' => 'Amount Reserved',
    'rollover' => 'Rollover',
    'amount_cleared_net' => 'Amount Cleared Net',
    'amount_used' => 'Amount Used',
    'amount_remaining' => 'Amount Remaining',
    'amount_available' => 'Amount Available',
    'expired' => 'Expired',
    'tcc_pin' => 'TCC PIN',
    'notes' => 'Notes',
    'allocated_trades' => 'Allocated Trades',
    'tax_case_no' => 'Tax Case No',
    'tax_cert_pdf' => 'Tax Cert PDF',
    'created_at' => 'Created at',
    'created_by' => 'Created by',
    'updated_at' => 'Updated at',
    'updated_by' => 'Updated by'
  ];


  // NOTE: Allocations before 2023 were not tracked and we need
  // to look at the `rollover` column value to determine which 2022
  // TCCs were not fully spent and had amounts remaining to use in 2023.
  // The `rollover` column will always only apply to TCCs issued before 2023.
  // We assume rollover amounts already cater for reserved amounts.
  // NOTE: `amount_reserved` is the PIN amount not accessable in this context,
  // since it was / will be used in another context.
  public function getAmountClearedNet( $tcc )
  {
    $rollover = $tcc->rollover + 0;
    $tcc->amount_cleared_net = $rollover ?: $tcc->amount_cleared - $tcc->amount_reserved;
    return $tcc->amount_cleared_net;
  }


  public function getAmountUsed( $tcc )
  {
    if ( $tcc->amount_remaining ) {
      $tcc->amount_used = $tcc->amount_cleared_net - $tcc->amount_remaining;
    }
    return $tcc->amount_used ?: 0;
  }

  public function getAmountRemaining( $tcc )
  {
    $tcc->amount_remaining = $tcc->amount_cleared_net - $tcc->amount_used;
    return $tcc->amount_remaining;
  }


  public function getAmountAvailable( $tcc )
  {
    $tcc->amount_available = $tcc->status !== 'Approved' ? 0 : $tcc->amount_remaining;
    return $tcc->amount_available;
  }


  // We only work with TCCs that are / were valid in the current year.
  // i.e. TCCs that were not expired or fully spent before 1 Jan.
  public function getExpiresAt( $tcc )
  {
    return strtotime( $tcc->date . ' + 1 year' );
  }


  public function getFieldsMismatch( array $localFields, array $remoteFields ): array
  {
    $mismatchedItems = [];
    $keysLocal = array_keys($localFields);
    $keysRemote = array_keys($remoteFields);
    $lengthLocal = count($keysLocal);
    $lengthRemote = count($keysRemote);
    $minLength = min($lengthLocal, $lengthRemote);
    // If there is a key or field name mismatch at any column position, add it to $mismatchedItems
    for ($i = 0; $i < $minLength; $i++) {
      if ($keysLocal[$i] !== $keysRemote[$i] || $localFields[$keysLocal[$i]] !== $remoteFields[$keysRemote[$i]]) {
        $localField = isset($keysLocal[$i]) ? $keysLocal[$i] : 'null';
        $remoteField = isset($keysRemote[$i]) ? $keysRemote[$i] : 'null';
        $mismatchedItems[] = "Local ($localField, {$localFields[$keysLocal[$i]]}) doesn't match REMOTE ($remoteField, {$remoteFields[$keysRemote[$i]]})";
      }
    }
    // If $remoteFields has more items, add those to $mismatchedItems as well
    if ($lengthRemote > $lengthLocal) {
      for ($i = $minLength; $i < $lengthRemote; $i++) {
        $remoteKey = isset($keysRemote[$i]) ? $keysRemote[$i] : 'null';
        $mismatchedItems[] = "Local (none) doesn't match REMOTE ($remoteKey, {$remoteFields[$keysRemote[$i]]})";
      }
    }
    return $mismatchedItems;
  }


  public function getRemoteColumnHeader( $colName )
  {
    return isset($this->columnsMap[$colName]) ? $this->columnsMap[$colName] : null;
  }


  public function getRemoteColumnHeaders()
  {
    // Map to array values of $this->columnsMap
    $headers = array_values( $this->columnsMap );
    $headers[] = 'Row Num';
    return $headers;
  }


  public function mapGoogleTableHeaders( $googleTableHeaders )
  {
    $headers = array_map( function ( $googleTableHeader ) {
      return str_replace( ' ', '_', strtolower( $googleTableHeader ) );
    }, $googleTableHeaders );
    return $headers;
  }  


  /**
   * Map a tcc stdClass rec to a remote tcc stdClass rec
   * The remote rec is an array of values, with indexes matching the column names in remote column headers order.
   */
  public function mapTccToRemoteRow( $tcc )
  {
    $remote = [];
    $remote[] = $tcc->tcc_id;
    $remote[] = $tcc->client_id;
    $remote[] = $tcc->status;
    $remote[] = $tcc->application_date;
    $remote[] = $tcc->date;
    $remote[] = $tcc->amount_cleared;
    $remote[] = $tcc->amount_reserved;
    $remote[] = $tcc->rollover ?: null;
    $remote[] = $this->getAmountClearedNet( $tcc );
    $remote[] = $this->getAmountUsed( $tcc );
    $remote[] = $this->getAmountRemaining( $tcc );
    $remote[] = $this->getAmountAvailable( $tcc );
    $remote[] = $tcc->expired;
    $remote[] = $tcc->tcc_pin;
    $remote[] = $tcc->notes;
    $remote[] = $tcc->allocated_trades;
    $remote[] = $tcc->tax_case_no;
    $remote[] = $tcc->tax_cert_pdf;
    $remote[] = $tcc->created_at;
    $remote[] = $tcc->created_by;
    $remote[] = $tcc->updated_at;
    $remote[] = $tcc->updated_by;
    $remote[] = null; // Row Num (never set this value!)    
    return $remote;
  }


  public function mapToTccData(array $keys, array $values): array
  {
    $tccData = [];
    foreach ( $keys as $index => $key ) {
      $tccData[$key] = isset( $values[$index] ) ? $values[$index] : null;
    }
    return $tccData;
  }

} // TccS2Mapper