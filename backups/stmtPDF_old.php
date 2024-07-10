<?php /* Client Statement PDF Template */


use F1\PDF;


function money( $value, $decimalPlaces = 2, $symbol = 'R' ) {
  return currency( $value, $symbol, ',', $decimalPlaces );
}


function dotdot( $str, $len )
{
  if ( empty( $str ) ) return '';
  $str = escape( $str, 'convert-enc' );
  return ( strlen( $str ) > $len ) ? substr( $str, 0, $len ) . '...' : $str;
}


$initialCapital = $statement->getData( 'initialCapital' );
$sdaMandateRemaining = $statement->getData( 'sdaMandateRemaining' );
$fiaMandateRemaining = $statement->getData( 'fiaMandateRemaining' );
$totalNetProfit = $statement->getData( 'totalNetProfit' );


$pdf = new PDF( 'P', 'mm', 'letter' );

$pdf->SetMargins( 21.5, 18.9, 21.5 );

$fs0 = 6.5;
$fs1 = 7;
$fs2 = 8.5;
$fs3 = 10;

$w1 = 55;
$w2 = 22;
$w3 = 42;
$w4 = 68;
$w5 = 26;
$w6 = 153;

$p1 = 1;
$p3 = 3;

$banner = realpath( __ROOT_DIR__ . '/assets/img/brand/header.jpg' );

// First page
$pdf->AddPage();

$pdf->SetDrawColor( 0 );

$pdf->Image( $banner, 0, 0, 177, 26.7, 0, 0, 'next-newline' );

$top = $pdf->getY() + 4;

$i = 1;
$dy = 0.5;
$lm = $pdf->getX();
$bgCol = ($i%2) ? '' : '#eeeeee';
$pdf->SetMargin( 'left', $lm );
$pdf->TextBoxSL( -2, 4,   $w1, dotdot( $client->name, 47 ), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w1, dotdot( $client->address, 47 ), 'L', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w1, dotdot( $client->suburb, 47 ), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w3, dotdot( $client->city, 27 ), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w2, dotdot( $client->postal_code, 27), 'L', 'next-newline', $fs1, '#000000', '', '', 0, $bgCol );

$pdf->setXY( $pdf->getX() + $w1 + 3, $top );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w2, 'Trading Capital', 'C', 'next-newline', $fs1, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w2, money( $initialCapital ), 'C', 'next-newline', $fs1, '#6677ff', '', '', 0, $bgCol );

$pdf->setXY( $pdf->getX() + $w2 + 6, $top );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w3, 'Mandate Remaining', 'C', 'next-newline', $fs1, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w2, money( $sdaMandateRemaining ), 'R', 'next-inline', $fs1, '#6677ff', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, 0, $w2, 'SDA', 'L', 'next-newline', $fs1, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w2, money( $fiaMandateRemaining ), 'R', 'next-inline', $fs1, '#6677ff', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, 0, $w2, 'FIA', 'L', 'next-newline', $fs1, '#000000', 'B', '', 0, $bgCol );

$pdf->setXY( $pdf->getX() + $w3 + 7.67, $top );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w3, 'Blockchain Currency Hub (Pty) Ltd', 'R', 'next-newline', $fs0, '#777777', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w3, 'Constantia, Cape Town, 7966', 'R', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );
$pdf->TextBoxSL( -2, $dy, $w3, 'www.CurrencyHub.co.za', 'R', 'next-newline', $fs0, '#6677ff', 'U', '', 0, $bgCol, 0, 0, 'https://www.currencyhub.co.za' );
$pdf->TextBoxSL( -2, $dy, $w3, 'support@currencyhub.co.za', 'R', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );

$pdf->setXY( $lm + $w3 + 9, $top + 15.3 );
$pdf->SetMargin( 'left', $pdf->getX() );
$pdf->TextBoxSL( -2, $dy, $w4, 'CURRENCY HUB Client Statement of Accounts', 'C', 'next-newline', $fs2, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, 1, $w4, "01 January $year  -  31 December $year", 'C', 'next-newline', $fs0, '#000000', '', '', 0, $bgCol );

$pdf->setXY( $lm, $top + 28 );
$pdf->SetMargin( 'left', $lm );
$pdf->TextBoxSL( -2, $dy, $w3, 'Arbitrage Trade Summary', 'L', 'next-inline', $fs0, '#000000', 'B', '', 0, $bgCol );

$pdf->setX( 150 );
$dateStr = ( date( 'Y' ) == $year ) ? date('d F Y') : "31 December $year";
$pdf->TextBoxSL( -2, 0, $w2, 'Statement Date', 'R', 'next-inline', $fs0, '#000000', 'B', '', 0, $bgCol );
$pdf->TextBoxSL( -2, 0, $w5, $dateStr, 'R', 'next-newline', $fs0, '#000000', 'B', '', 0, $bgCol );

$pdf->TextBoxSL( -2, 1.67, $w2, 'Trade Date', 'L', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'Capital Traded', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'SDA / FIA', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'TUSD Bought', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'OTC Rate', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'Net Capital', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'Net Profit (R)', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );
$pdf->TextBoxSL( -2, 0, $w2, 'Net Return (%)', 'C', 'next-newline', $fs0, '#ffffff', 'B','' , '', '#000000', 0, $p3 );


foreach( $lines as $line )
{
  $tradeDate = date( 'Y/m/d', strtotime( $line->date ) );
  $tradeAmount = money( $line->zar_sent );
  $tradeType = $line->sda_fia;
  $tusdBought = money( $line->usd_bought, 2, '$' );
  $fxRateZAR = money( $line->forex_rate, 3 );
  $netCapital = money( $line->net_capital );
  $netProfit = money( $line->net_profit );
  $netReturn = number_format( $line->net_return, 2 ) . '%';
  $pdf->TextBoxSL( -2, 1, $w2, $tradeDate, 'L', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $tradeAmount, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $tradeType, 'L', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $tusdBought, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $fxRateZAR, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $netCapital, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $netProfit, 'R', 'next-inline', $fs0, '#000000', '','' , 0, $bgCol );
  $pdf->TextBoxSL( -2, 0, $w2, $netReturn, 'R', 'next-newline', $fs0, '#000000', '','' , 0, $bgCol );
}

$pdf->setXY( 131.5, $pdf->getY() + 4 );
$pdf->SetMargin( 'left', $lm );
$pdf->TextBoxSL( -2, 0, $w2, 'Totals', 'R', 'next-inline', $fs0, '#000000', 'B', '', '', $bgCol );
$pdf->TextBoxSL( -2, 0, $w2, money( $totalNetProfit ), 'R', 'next-newline', $fs0, '#000000', '', '', '', $bgCol );


$pdf->TextBoxSL( -2, 7, $w6, 'Earn 5% of CURRENCY HUB profits for new clients you refer!', 'C', 'next-newline', $fs0, '#ffffff', 'B', '' , 0, '#000000', 0, 0 );
$pdf->TextBoxSL( -2, -0.1, $w6, 'Login to the website for more info and to get your unique referral link. T&Cs Apply', 'C', 'next-inline', $fs0, '#ffffff', 'B','' , 0, '#000000', 0 ,0 );

$pdf->TextBoxSL( -2, -3.1, $w2, 'CLICK HERE', 'C', 'next-newline', $fs0, '#6677ff', 'U', '', 1, $bgCol, 0, $p3, 'https://www.currencyhub.co.za/customer-dashboard/ch-referral/' );


$pdf->TextBoxSL( -1, 4, 0, 'BLOCKCHAIN CURRENCY HUB (Pty) Ltd (2019/431753/07) trading as CURRENCY HUB - FSP Juristic Representative (50850)', 'C', 'next-newline', 4.34, '#777777' );
$pdf->TextBoxSL( -1, -0.5, 0, 'Directors: A.Ludwig and D.Farelo - Constantia, 7966, Western Cape', 'C', 'next-newline', 4.34, '#777777' );