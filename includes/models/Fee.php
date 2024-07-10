<?php namespace App\Models;

use stdClass;
use Exception;


class Fee {

  private $app;


  public function __construct( $app )
  {
    $this->app = $app;
  }


  public function getChFee( $date = null )
  {
    debug_log( $date, 'Fee::getChFee(), date = ', 2 );

    $feeType = 1; // Currency Hub Fee Type

    if ( ! $date ) $date = date( 'Y-m-d' );

    $fee = $this->app->db->table('fees')
      ->where( 'fee_type_id', $feeType )
      ->where( 'start_date', '<=', $date )
      ->where( [ [ 'end_date', 'IS', null ], [ 'end_date', '>=', $date, 'OR' ] ] )
      ->getFirst();

    if ( ! $fee ) throw new Exception( 
      'Fee::getChFee(), CH Fee not found for date ' . $date );

    return $fee->amount;
  }


  public function getSwiftFee( $bank, $date = null )
  {
    switch ( $bank ) {
      case 'Mercantile': $feeType = 2; break;
      case 'Capitec':    $feeType = 3; break;
      case 'Investec':   $feeType = 4; break;
      default: throw new Exception( "Fee::getSwiftFee(), Bank $bank not supported" );
    }
    
    if ( ! $date ) $date = date( 'Y-m-d' );

    $fee = $this->app->db->table('fees')
      ->where( 'fee_type_id', $feeType )
      ->where( 'start_date', '<=', $date )
      ->where( [ [ 'end_date', 'IS', null ], [ 'end_date', '>=', $date, 'OR' ] ] )
      ->getFirst();

    if ( ! $fee ) throw new Exception( 
      "Fee::getSwiftFee(), Swift Fee not found for bank $bank and date $date" );

    return $fee->amount;
  }

} // Fee
