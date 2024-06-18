<?php namespace App\Models;


class TradeS2Mapper {


  // Used in tades API to map remote trade data to local trade data
  public function mapTradeRowValues( $values )
  {
    $trade = [];
    $trade['trade_id'] = $values[0] ?? null;
    $trade['otc'] = $values[1] ?? null;
    $trade['date'] = $values[2] ?? null;
    $trade['client_id'] = $values[3] ?? null;
    $trade['sda_fia'] = $values[4] ?? null;
    $trade['zar_sent'] = decimal($values[5]) ?? null;
    $trade['usd_bought'] = $values[6] ?? null;
    $trade['trade_fee'] = $values[7] ?? null;
    $trade['forex_rate'] = $values[8] ?? null;
    $trade['zar_profit'] = $values[9] ?? null;
    $trade['percent_return'] = $values[10] ?? null;
    $trade['fee_category_percent_profit'] = $values[11] ?? null;
    $trade['recon_id1'] = $values[12] ?? null;
    $trade['recon_id2'] = $values[13] ?? null;
    $trade['forex_reference'] = $values[14] ?? null;
    $trade['otc_reference'] = $values[15] ?? null;
    $trade['forex'] = $values[16] ?? null;
    $trade['amount_covered'] = $values[17] ?? null;
    $trade['allocated_pins'] = $values[18] ?? null;
    return $trade;
  }


  public function mapTradeToRemoteRow( $trade )
  {
    $remote = [];
    $remote[] = $trade->trade_id;
    $remote[] = $trade->otc;
    $remote[] = $trade->date;
    $remote[] = $trade->client_id;
    $remote[] = $trade->sda_fia;
    $remote[] = $trade->zar_sent;
    $remote[] = $trade->usd_bought;
    $remote[] = round( $trade->trade_fee?:0, 3 ) / 100; // should be = $val . '%' ?
    $remote[] = $trade->forex_rate;
    $remote[] = $trade->zar_profit;
    $remote[] = round( $trade->percent_return?:0, 3 ) / 100;
    $remote[] = round( $trade->fee_category_percent_profit?:0, 3 ) / 100;
    $remote[] = $trade->recon_id1;
    $remote[] = $trade->recon_id2;
    $remote[] = $trade->forex_reference;
    $remote[] = $trade->otc_reference;
    $remote[] = $trade->forex;
    $remote[] = $trade->amount_covered;
    $remote[] = $trade->allocated_pins;
    $remote[] = null; // Row Num (never set this value!)
    return $remote;
  }


  public function getRemoteColumnHeaders()
  {
    return [
      'Trade ID',
      'OTC',
      'Trade Date',
      'Client ID',
      'SDA / FIA',
      'ZAR Sent to Currencies Assist',
      'TUSD Bought ($)',
      'Trade Fee (%)',
      'TUSD Price (R)',
      'Profit (R)',
      'Return (%)',
      'Fee Category (% Profit)',
      'Recon OVEX ID',
      'Recon OVEX ID2',
      'Mercantile Reference',
      'OTC Ref',
      'FOREX',
      'Amount Covered',
      'Allocated PINs',
      'Row Num',
    ];
  }


  public function getRemoteColumnHeader( $colName )
  {
    $headersMap = [
      'trade_id' => 'Trade ID',
      'otc' => 'OTC',
      'date' => 'Trade Date',
      'client_id' => 'Client ID',
      'sda_fia' => 'SDA / FIA',
      'zar_sent' => 'ZAR Sent to Currencies Assist',
      'usd_bought' => 'TUSD Bought ($)',
      'trade_fee' => 'Trade Fee (%)',
      'forex_rate' => 'TUSD Price (R)',
      'zar_profit' => 'Profit (R)',
      'percent_return' => 'Return (%)',
      'fee_category_percent_profit' => 'Fee Category (% Profit)',
      'recon_id1' => 'Recon OVEX ID',
      'recon_id2' => 'Recon OVEX ID2',
      'forex_reference' => 'Mercantile Reference',
      'otc_reference' => 'OTC Ref',
      'forex' => 'FOREX',
      'amount_covered' => 'Amount Covered',
      'allocated_pins' => 'Allocated PINs',
      'row_num' => 'Row Num',
    ];
    return isset( $headersMap[$colName] ) ? $headersMap[$colName] : null;
  }


  public function mapToTradeData(array $keys, array $values): array
  {
    $tradeData = [];
    foreach ($keys as $index => $key) {
      $tradeData[$key] = isset( $values[$index] ) ? $values[$index] : null;
    }
    return $tradeData;
  }

} // TradeS2Mapper
