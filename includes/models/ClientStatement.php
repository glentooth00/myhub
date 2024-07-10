<?php namespace App\Models;

use stdClass;
use Exception;

use App\Models\Fee as FeeModel;
use App\Models\Client as ClientModel;


class ClientStatement {

  private $app;

  public $data = [];


  public function __construct( $app )
  {
    $this->app = $app;
  }


  public function getData( $key = 'all', $default = null )
  {
    return $key !== 'all'
      ? $this->data[ $key ] ?? $default
      : $this->data;
  }


  public function addData( $key, $value )
  {
    $this->data[ $key ] = $value;
  }


 /**
   * @param mixed $client Client UID or object e.g. '139c0229' or $clientObject
   * @param array $options e.g. [ 'year' => 2020 ]
   */
  public function generate( $client, $options = [] )
  {
    debug_log( $client, 'ClientStatement::generate(), client = ', 4 );
    debug_log( $options, 'ClientStatement::generate(), options = ', 2 );

    $app = $this->app;

    $thisYear = date( 'Y' );
    $theYear = $options['year'] ?? $thisYear;

    $clientModel = new ClientModel( $app );

    $client = $this->resolveClient( $client );
    if ( ! $client or empty( $client->name ) ) throw new Exception(
      'ClientStatement: Client not found or invalid.' );

    $trades = $options['trades'] ?? $clientModel->getClientTrades( 
      $client, ['type' => 'All', 'year' => $theYear] );

    debug_log( PHP_EOL, '', 6 );
    debug_log( $trades, 'ClientStatement::generate(), trades = ', 6 );

    debug_log( PHP_EOL, '', 2 );
    $annualInfo = $clientModel->getAnnualInfo( $client, $theYear );
    debug_log( $annualInfo, 'ClientStatement::generate(), annualInfo = ', 2 );

    $totalSDA = 0;
    $totalFIA = 0;
    $sdaCount = 0;
    $fiaCount = 0;
    $bothCount = 0;
    $totalNetProfit = 0;
    $initialCapital = $annualInfo->trading_capital ?? 0;

    $feeModel = new FeeModel( $app );
    // NOTE: We assume the CH Trading Fee is the same for the whole year.
    $chTradingFee = $feeModel->getChFee( "$theYear-01-01" );
    debug_log( $chTradingFee, 'ClientStatement::generate(), chTradingFee = ', 2 );

    // TODO: Link swiftFee to the bank of the client?
    $swiftFee = $feeModel->getSwiftFee( 'Capitec', "$theYear-01-01" );
    debug_log( $swiftFee, 'ClientStatement::generate(), Capitec Swift Fee = ', 2 );


    $lines = [];
    foreach ( $trades as $trade ) {
      $lines[] = $this->toStatementLine( $trade, $chTradingFee, $swiftFee );
    }

    // $swiftFee = 500;
    // foreach( $lines as $line )
    // {
    //   $tradeTime = strtotime( $line->date );
    //   $tradeDate = date( 'Y/m/d', $tradeTime );
    //   $tradeAmount = decimal( $line->zar_sent, 0 );
    //   $netProfit = decimal( $line->net_profit, 0 );
    //   if ( $tradeTime < strtotime( '2024-05-09' ) ) { // Before the fee was introduced on 9 May 2024
    //     $grossCapital = $tradeAmount;
    //     $netCapital = $tradeAmount - $swiftFee;
    //   } else {
    //     $grossCapital = $tradeAmount + $swiftFee;
    //     $netCapital = $tradeAmount;
    //   }
    //   $tradeType = $line->sda_fia;
    //   $tusdBought = money( $line->usd_bought, 2, '$' );
    //   $fxRateZAR = money( $line->forex_rate, 3 );
    //   $balance = money( $grossCapital + $netProfit );
    //   $netReturn = number_format( $line->net_return, 2 ) . '%';

    foreach ( $lines as $line ) {
      if ( $line->sda_fia == 'SDA' )
      { 
        $totalSDA += $line->zar_sent;
        $totalNetProfit += $line->net_profit;
        $sdaCount++;
      }
      else if ( $line->sda_fia == 'FIA' ) {
        $totalFIA += $line->zar_sent;
        $totalNetProfit += $line->net_profit;
        $fiaCount++;
      }
    }

    // Must be BEFORE we calc SDA/FIA contributions.
    $fiaArroved = $client->fia_approved ?? 0;
    $sdaMandate = $annualInfo->sda_mandate ?? 0;
    $fiaMandate = $annualInfo->fia_mandate ?? 0;
    $sdaMandateRemaining = max( $sdaMandate - $totalSDA, 0 );
    $fiaMandateRemaining = max( $fiaMandate - $totalFIA, 0 );
    $fiaApprovedAvail = max( $fiaArroved - $totalFIA, 0 );
    $fiaAvail = min( $fiaApprovedAvail, $fiaMandateRemaining );

    // We need to handle SDA/FIA after all the specific SDA or FIA trades have been processed
    // to see if there is any remaining mandate to allocate to SDA/FIA trades.
    foreach ( $lines as $line ) {
      if ( $line->sda_fia != 'SDA/FIA' ) continue;
      $pins = $line->allocated_pins ? json_decode( $line->allocated_pins, true ) : [];
      $sdaPart = $pins['_SDA_'] ?? 0;
      if ( ! $sdaPart and $sdaMandateRemaining ) {
        $sdaPart = min( $line->zar_sent, $sdaMandateRemaining );
        $sdaMandateRemaining -= $sdaPart;
      }
      $fiaPart = $line->zar_sent - $sdaPart;
      $totalFIA += $fiaPart;
      $totalSDA += $sdaPart;
      $totalNetProfit += $line->net_profit;
      $bothCount++;
    }

    $totalNetReturn = $initialCapital ? $totalNetProfit / $initialCapital * 100 : 0;

    // The data
    $this->addData( 'year', $theYear );
    $this->addData( 'date', ( $theYear == $thisYear ) ? date('d F Y') : "31 December $theYear" );
    $this->addData( 'dateRange', "01 January $theYear - 31 December $theYear" );
    $this->addData( 'client', $client );
    $this->addData( 'SDA', $sdaCount );
    $this->addData( 'FIA', $fiaCount );
    $this->addData( 'SFA', $bothCount );
    $this->addData( 'totalSDA', $totalSDA );
    $this->addData( 'totalFIA', $totalFIA );
    $this->addData( 'annualInfo', $annualInfo );
    $this->addData( 'totalTrades', count( $trades ) );
    $this->addData( 'chTradingFee', $chTradingFee );
    $this->addData( 'initialCapital', $initialCapital );
    $this->addData( 'totalNetProfit', round( $totalNetProfit, 2 ) );
    $this->addData( 'totalNetReturn', round( $totalNetReturn, 2 ) );
    $this->addData( 'sdaMandateRemaining', $sdaMandateRemaining );
    $this->addData( 'fiaMandateRemaining', $fiaMandateRemaining );
    $this->addData( 'sdaMandate', $sdaMandate );
    $this->addData( 'fiaMandate', $fiaMandate );
    $this->addData( 'fiaArroved', $fiaArroved );
    $this->addData( 'fiaAvail', $fiaAvail );

    debug_log( PHP_EOL, '', 3 );
    debug_log( $this->data, 'ClientStatement::generate(), data = ', 4 );

    $this->addData( 'lines', $lines );

    debug_log( PHP_EOL, '', 4 );
    debug_log( $lines, 'ClientStatement::generate(), lines = ', 5 );

    return $this->data;
  }


  public function resolveClient( $client )
  {
    if ( is_string( $client ) ) {
      $clientModel = new ClientModel( $this->app );
      $client = $clientModel->getClientByUid( $client );
    }
    return $client;
  }


  public function getTradeSDAPart( $trade )
  {
    if ( ! $trade->allocated_pins ) return 0;
    $pins = json_decode( $trade->allocated_pins, true );
    return isset( $pins['_SDA_'] ) ? $pins['_SDA_'] : 0;
  }


  public function tradeIsInhouseOTC( $trade )
  {
    $id = (string) $trade->trade_id;
    return $id ? $id[0] === 'C' : false;
  }


  public function tradeProfitShareFee( $trade )
  {
    // $inCapital = $trade->zar_sent;
    $grossProfit = $trade->zar_profit;
    $profitShareFee = round( $grossProfit * $trade->fee_category_percent_profit / 100, 3 );
    return $profitShareFee;
  }


  public function tradeNetProfit( $trade, $chTradingFee )
  {
    $grossProfit = $trade->zar_profit;
    $isInhouseOTC = $this->tradeIsInhouseOTC( $trade );
    $profitShareFee = $this->tradeProfitShareFee( $trade );
    $tradeNetProfit = round( $grossProfit - $profitShareFee - ( $isInhouseOTC ? 0 : $chTradingFee ), 3 );
    return $tradeNetProfit;
  }


  public function tradeNetReturn( $trade, $netProfit )
  {
    $inCapital = $trade->zar_sent;
    return $inCapital ? round( $netProfit / $inCapital * 100, 2 ) : 0;
  }


  public function toStatementLine( $trade, $chTradingFee, $swiftFee = 500 )
  {
    $tradeTime = strtotime( $trade->date );
    $tradeAmount = decimal( $trade->zar_sent, 0 );
    if ( $tradeTime < strtotime( '2024-05-09' ) ) { // Before the fee was introduced on 9 May 2024
      $grossCapital = $tradeAmount;
      $netCapital = $tradeAmount - $swiftFee;
    } else {
      $grossCapital = $tradeAmount + $swiftFee;
      $netCapital = $tradeAmount;
    }
    $line = clone $trade;
    $line->is_inhouse_otc = $this->tradeIsInhouseOTC( $trade );
    $line->gross_capital = $grossCapital;
    $line->swift_fee = $swiftFee;
    $line->net_capital = $netCapital;
    $line->net_profit = $this->tradeNetProfit( $trade, $chTradingFee );
    $line->payment = $grossCapital + $line->net_profit;
    $line->net_return = $this->tradeNetReturn( $trade, $line->net_profit );
    // $line->net_capital = $trade->zar_sent + $line->net_profit; // legacy code
    $line->profit_share_fee = $this->tradeProfitShareFee( $trade );
    return $line;
  }


  public function mapToS2Trade( $line ) {
    $s2Trade = new stdClass();
    $s2Trade->date = $line->date;
    $s2Trade->amount = $line->zar_sent;
    $s2Trade->type = $line->sda_fia;
    $s2Trade->tusd = $line->usd_bought;
    $s2Trade->rate = $line->forex_rate;
    $s2Trade->netProfit = $line->net_profit;
    $s2Trade->netReturn = $line->net_return / 100; // Div 100 important!
    return $s2Trade;
  }


  public function toS2Trades()
  {
    $s2Trades = [];
    foreach ( $this->getData( 'lines', [] ) as $line ) {
      $s2Trades[] = $this->mapToS2Trade( $line );
    }
    return $s2Trades;
  }


  public function toS2Data()
  {
    $data = new stdClass();
    $data->sdaUsed = $this->getData( 'totalSDA' );
    $data->fiaUsed = $this->getData( 'totalFIA' );
    $data->initialCapital = $this->getData( 'initialCapital' );
    $data->fiaMandateRemaining = $this->getData( 'fiaMandateRemaining' );
    $data->sdaMandateRemaining = $this->getData( 'sdaMandateRemaining' );
    $data->totalNetProfit = $this->getData( 'totalNetProfit' );
    $data->totalNetReturn = $this->getData( 'totalNetReturn' ); // divide by 100 ?
    // TODO: We need to look into when te following info is required and when not!
    // Not including the "trades" property, is telling the API to not generate a statement.
    // Used in S2::apiUpdateClient() to generate/update the client's statement file.
    // What about other types of update endpoints and use cases?
    // What about the clientTrades array? Only contains updated trades?
    $data->trades = $this->toS2Trades();
    return $data;
  }


  public function mapToCsvLine( $line )
  {
    return [
      $line->date,
      $line->gross_capital,
      $line->swift_fee,
      $line->net_capital,
      $line->sda_fia,
      $line->usd_bought,
      $line->forex_rate,
      $line->net_profit,
      $line->payment,
      $line->net_return
    ];
  }


  public function getHeader()
  {
    return [
      'Trade Date',
      'Gross Capital',
      'SWIFT Fee',
      'Net Capital',
      'SDA / FIA',
      'TUSD Bought',
      'OTC Rate',
      'Net Profit',
      'Payment',
      'Net Return (%)'
    ];  
  }


  public function toCsv()
  {
    $csv = [];
    $csv[] = implode( ',', $this->getHeader() );
    foreach ( $this->getData( 'lines', [] ) as $line ) {
      $csv[] = implode( ',', $this->mapToCsvLine( $line ) );
    }
    return implode( "\n", $csv );
  }


  // Control what gets printed 
  // by functions like print_r()
  public function __debugInfo()
  {
    return [
      'data' => $this->data
    ];
  }

} // ClientStatement
