<?php /* Client Statement PDF Template */

global $statement;


use F1\PDF;


function money( $value, $decimalPlaces = 2, $symbol = 'R' ) {
  return currency( $value, $symbol, ',', $decimalPlaces );
}


function dotdot( $str, $len )
{
  if ( empty( $str ) ) return '';
  $str = mb_convert_encoding( $str, 'UTF-8', 'UTF-8,ISO-8859-1' );
  return ( strlen( $str ) > $len ) ? substr( $str, 0, $len ) . '...' : $str;
}


$dateStr = $statement->getData( 'date' );
$dateRangeStr = $statement->getData( 'dateRange' );
$totalNetProfit = $statement->getData( 'totalNetProfit' );
// $initialCapital = $statement->getData( 'initialCapital' );
$sdaMandateRemaining = $statement->getData( 'sdaMandateRemaining' );
$fiaMandateRemaining = $statement->getData( 'fiaMandateRemaining' );
$sdaMandate = $statement->getData( 'sdaMandate' );
$fiaMandate = $statement->getData( 'fiaMandate' );
$fiaAvail = $statement->getData( 'fiaAvail' );
$client = $statement->getData( 'client' );
$lines = $statement->getData( 'lines' );


$pdf = new PDF( 'P', 'mm', 'letter' );

$pdf->SetMargins( 21.5, 18.9, 21.5 );

$fs0 = 6.5;
$fs1 = 7;
$fs2 = 8.5;
$fs3 = 10;

$k = 0.1875; // Google Sheet column width conversion factor

$w0 = 40 * $k;
$w1 = 80 * $k;
$w2 = 110 * $k;
$w3 = 80 * $k;
$w4 = 100 * $k;
$w5 = 70 * $k;
$w6 = 100 * $k;
$w7 = 80 * $k;
$w8 = 100 * $k;
$w9 = 110 * $k;
$w10 = 100 * $k;

$w12 = $w1 + $w2;
$w13 = $w1 + $w2 + $w3;
$w45 = $w4 + $w5;
$w47 = $w45 + $w6 + $w7;
$w67 = $w6 + $w7;
$w68 = $w67 + $w8;
$w910 = $w9 + $w10;
$w38 = $w3 + $w47 + $w8;
$w18 = $w12 + $w38;
$w17 = $w13 + $w47;
$w110 = $w18 + $w910;
$w19 = $w18 + $w9;

$p1 = 1;
$p3 = 3;

$banner = realpath( __ROOT_DIR__ . '/assets/img/brand/header.jpg' );

// First page
$pdf->AddPage();

$pdf->SetDrawColor( 0 );

$pdf->Image( $banner, 0, 0, 175, 28.5, 0, 0, 'next-newline' );

$top = $pdf->getY() + 4;

$i = 1;
$dy = 0.5;
$lm = $pdf->getX();
$bgCol = ($i%2) ? '' : '#eeeeee';
$pdf->SetMargin( 'left', $lm );
$pdf->TextBoxSL( -2, 4,   $w13, dotdot( $client->name, 32 ), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w13, dotdot( $client->address, 32 ), 'L', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w13, dotdot( $client->suburb, 32 ), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w13, dotdot( $client->city, 27 ), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w12, dotdot( $client->postal_code, 27), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );

$pdf->setXY( $pdf->getX() + $w12, $top );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w45, 'SDA REMAINING', 'C', 'next-newline', $fs1, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w45, money( $sdaMandateRemaining, 0 ).' / '.money( $sdaMandate, 0 ), 'C', 'next-newline', $fs1, '#999999', '', '', 0, $bgCol );

$pdf->setXY( $pdf->getX() + $w45, $top );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w45, 'AIT REMAINING', 'C', 'next-newline', $fs1, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w45, money( $fiaMandateRemaining, 0 ).' / '.money( $fiaMandate, 0 ), 'C', 'next-newline', $fs1, '#999999', '', '', 0, $bgCol );

$pdf->setXY( $pdf->getX() + $w45, $top );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w45, 'AIT AVAILABLE', 'C', 'next-newline', $fs1, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w45, money( $fiaAvail, 0 ), 'C', 'next-newline', $fs1, '#999999', '', '', 0, $bgCol );

$pdf->setXY( $pdf->getX() + $w12, $top );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w910, 'CURRENCY HUB MARKETS (PTY) LTD', 'R', 'next-newline', $fs0, '#777777', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w910, 'Constantia, Cape Town, 7966', 'R', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w910, 'www.CurrencyHub.co.za', 'R', 'next-newline', $fs0, '#6677ff', 'U', '', 0, $bgCol, 0, 0, 'https://www.currencyhub.co.za' );
$pdf->TextBoxSL( -2, $dy, $w910, 'support@currencyhub.co.za', 'R', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );

$pdf->setXY( $lm + $w12, $top + 15.3 );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w38, 'CURRENCY HUB Client Statement of Accounts', 'C', 'next-newline', $fs2, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, 1, $w38, $dateRangeStr, 'C', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );

$pdf->setXY( $lm, $top + 28 );
$pdf->SetMargin( 'left', $lm );
$pdf->TextBoxSL( -2, $dy, $w13, 'Arbitrage Trade Summary', 'L', 'next-inline', $fs0, '#000000', 'B', '', 0, $bgCol );

$pdf->setX( $lm + $w18 - 3 );
$pdf->TextBoxSL( -2, 0, $w9, 'Statement Date', 'R', 'next-inline', $fs0, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, 0, $w10 + 3, $dateStr, 'R', 'next-newline', $fs0, '#000000', 'B', '', 0, $bgCol );

$pdf->TextBoxSL( -2, 1.67, $w1, 'Trade Date', 'L', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'Gross Capital', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w3, 'SWIFT Fee', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w4, 'Net Capital', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w5, 'SDA / FIA', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w6, 'TUSD Bought', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w7, 'OTC Rate', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w8, 'Net Profit (R)', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w9, 'Payment', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w10, 'Net Ret. (%)', 'C', 'next-newline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );

foreach( $lines as $line )
{
  $tradeTime = strtotime( $line->date );
  $tradeDate = date( 'Y/m/d', $tradeTime );
  $grossCapital = money( $line->gross_capital );
  $swiftFee = money( $line->swift_fee );
  $netCapital = money( $line->net_capital );
  $tradeType = $line->sda_fia;
  $tusdBought = money( $line->usd_bought, 2, '$' );
  $fxRateZAR = money( $line->forex_rate, 3 );
  $netProfit = money( $line->net_profit, 2 );
  $payment = money( $line->payment, 2 );
  $netReturn = number_format( $line->net_return, 2 ) . '%';
  $pdf->TextBoxSL( -2, 1, $w1, $tradeDate, 'L', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $grossCapital, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w3, $swiftFee, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w4, $netCapital, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w5, $tradeType, 'L', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w6, $tusdBought, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w7, $fxRateZAR, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w8, $netProfit, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w9, $payment, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w10, $netReturn, 'R', 'next-newline', $fs0, '#000000', '','' , 0, $bgCol );
}

$pdf->setXY( $lm + $w17, $pdf->getY() + 4 );
$pdf->TextBoxSL( -2, 0, $w8, money( $totalNetProfit ), 'R', 'next-inline', $fs0, '#000000', 'B', '', '', $bgCol );

$pdf->setXY( $lm, $pdf->getY() + 7 );

$pdf->TextBoxSL( -2, -0.1, $w19, 'Earn 5% of CURRENCY HUB profits for new clients you refer!', 'C', 'next-newline', $fs0, '#ffffff', 'B', '' , 1, '#000000', 0, 0 );
$pdf->TextBoxSL( -2, -0.1, $w19, 'Login to the website for more info and to get your unique referral link. T&Cs Apply', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , 1, '#000000', 0 ,0 );

$pdf->TextBoxSL( -2, -3.1, $w10, 'CLICK HERE', 'C', 'next-newline', $fs0, '#6677ff', 'U', '', 1, $bgCol, 0, $p3, 'https://www.currencyhub.co.za/customer-dashboard/ch-referral/' );

$pdf->TextBoxML(-2, 0, $w110, 'Effective from May 9, 2024, we have implemented a change to enhance our service. ' . 
  'An additional R500 is added to your trading capital to cover the swift fee. This adjustment is designed to improve ' . 
  'your return on investment (ROI).', 'C', 'next-newline', $fs0, '#000000', 'B', '', 'LRB', $bgCol);

$pdf->SetMargin( 'left', $lm );
$pdf->TextBoxSL( -1, 3, 0, 'CURRENCY HUB MARKETS (PTY) LTD (2023/572397/07) trading as CURRENCY HUB is a Juristic Representative of BLACK ONYX FUND HUB (FSP 50850)', 'C', 'next-newline', 4.34, '#777777' );
$pdf->TextBoxSL( -1, -0.5, 0, 'The data is sourced from all trading and forex activities, compiled in-house and audited independently.', 'C', 'next-newline', 4.34, '#777777' );
$pdf->TextBoxSL( -1, -0.5, 0, 'PAST RETURNS DO NOT GUARANTEE FUTURE PERFORMANCE', 'C', 'next-newline', 4.34, '#777777' );