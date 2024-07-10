<?php /* Admin Module - Trades SPA - Trades List Sub Controller */ 

global $app;

use App\Models\Trade as TradeModel;
use App\Models\UserSettings as UserSettingsModel;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) { exit; }



// ---------
// -- GET --
// ---------

/* settings */
$settings = new UserSettingsModel( $app );


/* request */
$days = $_GET['days'] ?? $settings->getSettingValue( 'trades_days', 'this-week' );
$settings->saveIfChanged( 'trades_days', $days );


/* lists */
$tradeModel = new TradeModel( $app );
$trades = $tradeModel->getTrades( [ 'days' => $days ] );

$super = in_array( $app->user->role,  [ 'super-admin', 'sysadmin' ] );