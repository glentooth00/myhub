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

    $theYear = $options['year'] ?? date( 'Y' );

    $clientModel = new ClientModel( $app );

    $client = $this->resolveClient( $client );
    if ( ! $client or empty( $client->name ) ) throw new Exception(
      'ClientStatement: Client not found or invalid.' );

    $trades = $options['trades'] ?? $clientModel->getClientTrades( 
      $client, ['type' => 'All', 'year' => $theYear] );

    debug_log( PHP_EOL, '', 4 );
    debug_log( $trades, 'ClientStatement::generate(), trades = ', 4 );

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

    $lines = [];
    foreach ( $trades as $trade ) {
      $lines[] = $this->toStatementLine( $trade, $chTradingFee );
    }

    foreach ( $lines as $line ) {
      if ( $line->sda_fia == 'SDA' ) { $totalSDA += $line->zar_sent; $totalNetProfit += $line->net_profit; }
      else if ( $line->sda_fia == 'FIA' ) { $totalFIA += $line->zar_sent; $totalNetProfit += $line->net_profit; }
    }

    // Must be BEFORE we calc SDA/FIA contributions.
    $fiaMandateRemaining = ( $annualInfo->fia_mandate ?? 0 ) - $totalFIA;
    $sdaMandateRemaining = ( $annualInfo->sda_mandate ?? 0 ) - $totalSDA;

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
    }

    $totalNetReturn = $initialCapital ? $totalNetProfit / $initialCapital * 100 : 0;

    // The data
    $this->addData( 'year', $theYear );
    $this->addData( 'lines', $lines );
    $this->addData( 'client', $client );
    $this->addData( 'SDA', $sdaCount );
    $this->addData( 'FIA', $fiaCount );
    $this->addData( 'SFA', $bothCount );
    $this->addData( 'totalSDA', $totalSDA );
    $this->addData( 'totalFIA', $totalFIA );
    $this->addData( 'annualInfo', $annualInfo );
    // $this->addData( 'trades', $trades );
    $this->addData( 'totalTrades', count( $trades ) );
    $this->addData( 'chTradingFee', $chTradingFee );
    $this->addData( 'initialCapital', $initialCapital );
    $this->addData( 'totalNetProfit', round( $totalNetProfit, 2 ) );
    $this->addData( 'totalNetReturn', round( $totalNetReturn, 2 ) );
    $this->addData( 'fiaMandateRemaining', $fiaMandateRemaining );
    $this->addData( 'sdaMandateRemaining', $sdaMandateRemaining );

    // debug_log( $this->data, 'ClientStatement::generate(), data = ', 4 );

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


  public function toStatementLine( $trade, $chTradingFee )
  {
    $line = clone $trade;
    $line->net_profit = $this->tradeNetProfit( $trade, $chTradingFee );
    $line->net_return = $this->tradeNetReturn( $trade, $line->net_profit );
    $line->net_capital = $trade->zar_sent + $line->net_profit;
    $line->is_inhouse_otc = $this->tradeIsInhouseOTC( $trade );
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
      $line->zar_sent,
      $line->sda_fia,
      $line->usd_bought,
      $line->forex_rate,
      $line->net_capital,
      $line->net_profit,
      $line->net_return
    ];
  }


  public function getHeader()
  {
    return [
      'Trade Date',
      'Capital Traded',
      'SDA / FIA',
      'TUSD Bought',
      'OTC Rate',
      'Net Capital',
      'Net Profit (R)',
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
