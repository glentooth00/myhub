<?php /* Admin Module - Tools Push Data SPA - Show Sub Controller */

use App\Models\Tcc as TccModel;
use App\Models\Trade as TradeModel;
use App\Models\Client as ClientModel;



// -------------
// -- REQUEST --
// -------------




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 3 );



    /** ACTION 1 **/

    if ( $action === 'pushData' )
    {
      $db = use_database();

      $table = $_POST['table'] ?? null;


      // -------------------
      // Push to Remote: S2
      // -------------------

      $userEmail = 'neels@currencyhub.co.za';      

      switch ( $table )
      {
        case 'tccs':
          $tccModel = new TccModel( $db );

          $tccs = $db->table('tccs')->getAll();

          $rows = [];
          foreach ( $tccs as $tcc ) {
            $rows[] = $tccModel->mapTccToRemoteRow( $tcc );
          }

          $headers = $tccModel->getRemoteColumnHeaders();

          $payload = [ 'sheetName' => 'S3_Tccs', 'primaryKey' => 'TCC ID',
            'headers' => $headers, 'rows' =>  $rows ];

          run_google_script( 'createSheet', $payload, $userEmail );

          break;


        case 'trades':
          $tradeModel = new TradeModel( $db );

          $trades = $db->table('trades')->getAll();

          $rows = [];
          foreach ( $trades as $trade ) {
            $rows[] = $tradeModel->mapTradeToRemoteRow( $trade );
          }

          $headers = $tradeModel->getRemoteColumnHeaders();

          $payload = [ 'sheetName' => 'S3_Trades', 'primaryKey' => 'Trade ID',
            'headers' => $headers, 'rows' =>  $rows ];

          run_google_script( 'createSheet', $payload, $userEmail );

          break;


          case 'clients':
            $clientModel = new ClientModel( $db );

            $clients = $db->table('clients')->getAll(
              'client_id,name,status,trading_capital,sda_mandate,fia_mandate,
                fia_approved,sda_used,fia_used,fia_pending,fia_declined' );

            $rows = [];
            foreach ( $clients as $client ) {
              $row = [
                $client->client_id,
                $client->status,
                $client->trading_capital,
                $client->sda_mandate,
                $client->fia_mandate,
                $client->fia_approved,
                $client->sda_used,
                $client->fia_used,
                $client->fia_pending,
                $client->fia_declined
              ];
              $rows[] = $row;
            }

            $headers = [
                'Client ID',
                'Status',
                'Trading Capital',
                'SDA Mandate',
                'FIA Mandate',
                'FIA Approved',
                'SDA Used',
                'FIA Used',
                'FIA Pending',
                'FIA Declined'
            ];
            
            debug_log($headers, 'Client headers:', 2);
            debug_log($rows[0], 'Client rows[0]:', 2);

            $payload = [ 'sheetName' => 'S3_Clients', 'primaryKey' => 'Client ID',
              'headers' => $headers, 'rows' =>  $rows ];

            run_google_script( 'createSheet', $payload, $userEmail );

            break;

        default:
          throw new Exception( 'Invalid table' );
      }

    	json_response( [ 'success' => true, 'message' => "Push `$table` complete!" ] );

    } // test



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );

  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    $app->db->safeRollBack();
    json_response( [ 'success' => false, 'message' => $error ] );
  }

}




// ---------
// -- GET --
// ---------
