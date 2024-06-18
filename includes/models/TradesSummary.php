<?php namespace App\Models;


class TradesSummary {

  public $trades;

  public $zar_sent = 0;
  public $usd_bought = 0;
  public $sda_covered = 0;
  public $fia_covered = 0;
  public $amount_covered = 0;
  public $sda_covered_usd = 0;
  public $fia_covered_usd = 0;
  public $sda_used = 0;
  public $fia_used = 0;


  public function __construct( array $trades )
  {
    $this->trades = $trades;
    $this->calculate();
  }


  public function toUsdAllocations( $zarAllocations, $usdBought, $zarUsed )
  {
    $usdAllocations = [];
    foreach( $zarAllocations as $pin => $coverAmount ) {
      $coverAsPercentOfZarUsed = $coverAmount / $zarUsed;
      $coverUSD = $usdBought * $coverAsPercentOfZarUsed;
      $usdAllocations[$pin] = number_format( $coverUSD, 3, '.', '' );
    }
    return $usdAllocations;
  } 

  
  public function calculate()
  {
    foreach( $this->trades as $trade ) {
      $zarUsed = $trade->zar_sent;
      $this->zar_sent += $trade->zar_sent;
      $this->usd_bought += $trade->usd_bought;
      $this->amount_covered += $trade->amount_covered;
      $coverAsPercentOfZarUsed = $trade->amount_covered / $zarUsed;
      $allocs = json_decode( $trade->allocated_pins?:'[]', true );
      if ( ! $allocs ) $allocs = [];
      $allocs_usd = $this->toUsdAllocations( $allocs, $trade->usd_bought, $zarUsed );
      $trade->allocated_pins_usd = $allocs_usd ? json_encode( $allocs_usd ) : null;
      if ( $trade->sda_fia == 'SDA' ) {
        $this->sda_covered += $trade->amount_covered;
        $this->sda_covered_usd += $trade->usd_bought * $coverAsPercentOfZarUsed;
        $this->sda_used += $trade->zar_sent;
      }
      if ( $trade->sda_fia == 'FIA' ) {
        $this->fia_covered += $trade->amount_covered;
        $this->fia_covered_usd += $trade->usd_bought * $coverAsPercentOfZarUsed;
        $this->fia_used += $trade->zar_sent;
      }
      if ( $trade->sda_fia == 'SDA/FIA' ) {
        // debug_log($allocs, 'SDA/FIA Alloc! ', 3);
        $sdaCovered = isset($allocs['_SDA_']) ? $allocs['_SDA_'] : 0;
        $sdaCoveredUsd = $sdaCovered ? $allocs_usd['_SDA_'] : 0;
        $this->sda_covered += $sdaCovered;
        $this->sda_used += $sdaCovered;
        $fiaCovered = $trade->amount_covered - $sdaCovered;
        $fiaCoveredUsd = $trade->usd_bought * $coverAsPercentOfZarUsed - $sdaCoveredUsd;
        $this->fia_covered += $fiaCovered;
        $this->fia_covered_usd += $fiaCoveredUsd;
        $this->fia_used += $fiaCovered;
      }
    }
  } // calculate

} // TradesSummary
