<?php /* Accountant SPA - Trades List - Sub Controller */ 

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
$trades = $tradeModel->getTrades( [ 'days' => $days, 'client_accountant' => $app->user->first_name ] );