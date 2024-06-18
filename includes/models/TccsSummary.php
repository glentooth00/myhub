<?php namespace App\Models;


class TccsSummary {

  public $pinValues = 0;
  public $approved = 0;
  public $rollover = 0;
  public $remaining = 0;
  public $available = 0;
  public $allocated = 0;
  
  private $tccs;


  public function __construct( array $tccs )
  {
    $this->tccs = $tccs;
    $this->calculate();
  }


  public function calculate()
  {
    foreach( $this->tccs as $index => $tcc ) {
      $this->pinValues += $tcc->amount_cleared;
      $tcc->inPlay = (
        ( $tcc->status === 'Approved' && !$tcc->expired ) || 
        ( $tcc->rollover > 0 && ! $tcc->expired )
      );
      if ( $tcc->inPlay ) {
        $this->approved += $tcc->status == 'Approved' ? $tcc->amount_cleared_net : 0;
        $this->rollover += $tcc->rollover;
        $this->remaining += $tcc->amount_remaining;
        $this->available += $tcc->amount_available;
        $this->allocated += $tcc->amount_used;
      } else {
        if ( $tcc->status == 'Approved' && $tcc->expired ) {
          $tcc->status = '<span class="">Appr+Used <sub><small>' . $tcc->expired . '</small></sub></span>';
        }
      }
    }
  } // calculate

} // TccsSummary
