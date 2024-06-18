<?php namespace App\Models;

use stdClass;
use Exception;


class ClientS2Mapper {
  
  // Map Google Sheet Columns to MySql Table Columns
  public $columnsMap = [
    'Client ID'                 => 'client_id',
    'Name'                      => 'name',
    'Personal Email'            => 'personal_email',
    'Bank'                      => 'bank',
    'Accountant'                => 'accountant',
    'Status'                    => 'status',
    'Trader ID'                 => 'trader_id',
    'Trading Capital'           => 'trading_capital',
    'SDA Mandate'               => 'sda_mandate',
    'FIA Mandate'               => 'fia_mandate',
    'FIA Approved'              => 'fia_approved',
    'SDA Used'                  => 'sda_used',
    'FIA Used'                  => 'fia_used',
    'Inhouse Referrer 15%'      => 'inhouse_referrer_15_percent',
    '3rd Party Referrer'        => 'third_party_referrer',
    '3rd Party Profit %'        => 'third_party_profit_percent',
    'FX Intermediary'           => 'fx_intermediary',
    'Address'                   => 'address',
    'Suburb'                    => 'suburb',
    'City'                      => 'city',
    'Province'                  => 'province',
    'Country'                   => 'country',
    'Postal Code'               => 'postal_code',
    'OVEX Email'                => 'ovex_email',
    'Mercantile Name'           => 'mercantile_name',
    'BP Number'                 => 'bp_number',
    'CIF Number'                => 'cif_number',
    'FIA Pending'               => 'fia_pending',
    'FIA Declined'              => 'fia_declined',
    'OVEX Ref'                  => 'ovex_ref',
    'Capitec ID'                => 'capitec_id',
    'ID Number'                 => 'id_number',
    'Tax Number'                => 'tax_number',
    'Phone Number'              => 'phone_number',
    'Spare 1'                   => 'spare_1',      // 'marriage_cert',
    'Spare 2'                   => 'spare_2',      // 'crypto_declaration',
    'Spare 3'                   => 'spare_3',
    'Spare 4'                   => 'spare_4',
    'Spare 5'                   => 'spare_5',
    'Next Year\'s SDA Mandate'  => 'next_years_sda_mandate',
    'Next Year\'s FIA Mandate'  => 'next_years_fia_mandate',
    'Last Year\'s Statement'    => 'last_years_statement',
    'Statement File'            => 'statement_file',
    'Statement PDF'             => 'statement_pdf',
    'Last Action'               => 'last_action',
    'Action at'                 => 'action_at',
    'Action by'                 => 'action_by',
    'Updated at'                => 'updated_at',
    'Updated by'                => 'updated_by',
    'Created at'                => 'created_at',
    'Created by'                => 'created_by',
    'Settings'                  => 'settings',
    'Notes'                     => 'notes'
  ];


  public function removeLocalOnlyFields( array $fieldNames ): array
  {
    $localOnlyFields = [ 'id', 'first_name', 'middle_name', 'last_name', 'ncr', 
      'deleted_at', 'spouse_id', 'deleted_by', 'sync_at', 'sync_by', 'sync_from', 'sync_type' ];
    $filteredFields = array_filter( $fieldNames, function( $fieldName ) use ( $localOnlyFields ) {
      return !in_array( $fieldName, $localOnlyFields );
    } );
    // Re-index the array to reset the keys
    return array_values( $filteredFields );
  }


  public function getFieldsMismatch( array $localFields, array $remoteFields ): array
  {
    $mismatchedItems = [];
    $keysLocal = array_keys( $localFields );
    $keysRemote = array_keys( $remoteFields );
    $lengthLocal = count( $keysLocal );
    $lengthRemote = count( $keysRemote );
    $minLength = min( $lengthLocal, $lengthRemote );
    // If there is a key or field name mismatch at any column position, add it to $mismatchedItems
    for ( $i = 0; $i < $minLength; $i++ ) {
      if ( $keysLocal[$i] !== $keysRemote[$i] || $localFields[ $keysLocal[$i] ] !== $remoteFields[ $keysRemote[$i] ] ) {
        $localField = isset( $keysLocal[$i] ) ? $keysLocal[$i] : 'null';
        $remoteField = isset( $keysRemote[$i] ) ? $keysRemote[$i] : 'null';
        $mismatchedItems[] = "Local ( $localField, {$localFields[ $keysLocal[$i] ]} ) doesn't " .
          "match REMOTE ( $remoteField, {$remoteFields[ $keysRemote[$i] ]} )";
      }
    }
    // If $remoteFields has more items, add those to $mismatchedItems as well
    if ( $lengthRemote > $lengthLocal ) {
      for ( $i = $minLength; $i < $lengthRemote; $i++ ) {
        $remoteKey = isset( $keysRemote[$i] ) ? $keysRemote[$i] : 'null';
        $mismatchedItems[] = "Local (none) doesn't match REMOTE ( $remoteKey, {$remoteFields[ $keysRemote[$i] ]} )";
      }
    }
    return $mismatchedItems;
  }


  public function mapGoogleTableHeaders( $googleTableHeaders )
  {
    $headers = array_map( function ( $googleTableHeader ) {
      return str_replace(
        [ '3rd '  , ' %'      , '%'       , '\'', ' ' ],
        [ 'third_', '_percent', '_percent', ''  , '_' ],
        strtolower( $googleTableHeader )
      );
    }, $googleTableHeaders );
    return $headers;
  }


  /**
   * Map a client stdClass rec to a remote client stdClass rec
   * The remote rec is an array of values, with indexes matching the 
   * column names in remote column headers order.
   */
  public function mapClientToRemoteRow( $client ) {
    $remote = [];
    foreach ( $this->columnsMap as $sheetColumn => $dbColumn ) {
      $value = isset( $client->$dbColumn ) ? $client->$dbColumn : null;
      if ( $sheetColumn == '3rd Party Profit %' ) $value .= '%';
      $remote[] = $value;
    }
    return $remote;
  }


  public function mapRemoteRowToClient( array $remoteRow, array $localFields )
  {
    // debug_log( compact( 'remoteRow', 'localFields' ), 'mapRemoteRowToClient(), ', 2 );
    $client = new stdClass();
    foreach ( $localFields as $i => $s3FieldName ) {
      $client->$s3FieldName = $remoteRow[$i] ?? null;
    }
    return $client;
  }


} // ClientS2Mapper
