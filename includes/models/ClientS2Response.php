<?php namespace App\Models;

use stdClass;
use Exception;

use App\Models\Tcc as TccModel;
use App\Models\Trade as TradeModel;
use App\Models\Client as ClientModel;

use App\Models\TccS2Mapper;
use App\Models\TradeS2Mapper;
use App\Models\ClientS2Mapper;


/**
 * Map an updated client's SDA / FIA values and statement data
 * into a format we can include in a Google API call to S2.
 */

class ClientS2Response {

  private $app;

  public $options;

  public $data = [];


  public function __construct( $app, $options = [] )
  {
    $this->app = $app;
    $this->options = $options;
  }


  public function add( $key, $value )
  {
    $this->data[ $key ] = $value;
  }

 
  // $updateResult = The result of ClientState::updateStateFor()
  // $updateResult = [
  //   'statement' => ClientStatementModel,
  //   'client' =>  [
  //     'id' => $client->id,
  //     'fia_approved' => $fiaApprovedAtm,
  //     'fia_pending' => $totalFiaPending,
  //     'fia_declined' => $totalFiaDeclined,
  //     'sda_used' => $statement->getData( 'totalSDA' ),
  //     'fia_used' => $statement->getData( 'totalFIA' ),
  //     '...',
  //   ],
  //   'trades' => $updatedTrades,
  //   'tccs' => $updatedTccs,
  // ];  
  public function generate( array $updateResult )
  {
    $app = $this->app;

    $db = use_database();

    debug_log( PHP_EOL, 2 );
    $clientId = $updateResult['client']['id'] ?? null;
    debug_log( $clientId, 'ClientS2Response::generate(), For client id = ', 2 ); 
    if ( ! $clientId ) throw new Exception( "Client id not found or invalid." );

    $clientMapper = new ClientS2Mapper( $app );
    $client = $db->getFirst( 'clients', $clientId );
    if ( ! $client ) throw new Exception( "Client $clientId not found or invalid." );
    $client->city = escape( $client->city, 'fix-bad-encoding' ); // Temp patch - NM 12Jan24
    $clientRow = $clientMapper->mapClientToRemoteRow( $client );

    $updatedTCCRows = [];
    $tccMapper = new TccS2Mapper( $app );
    foreach ( $updateResult['tccs'] as $tcc ) {
      $updatedTCCRows[] = $tccMapper->mapTccToRemoteRow( $tcc );
    }

    $updatedTradeRows = [];
    $tradeMapper = new TradeS2Mapper( $app );
    foreach ( $updateResult['trades'] as $trade ) {
      $updatedTradeRows[] = $tradeMapper->mapTradeToRemoteRow( $trade );
    }

    // The payload

    $this->add( 'clientData', $clientRow );
    $this->add( 'clientTCCsData', $updatedTCCRows );
    $this->add( 'clientTradesData', $updatedTradeRows );

    if ( empty( $this->options['no-statement'] ) ) {
      $statement = $updateResult['statement'] ?? null;
      if ( ! $statement ) throw new Exception( 'Statement not found or invalid.' );
      $this->add( 'statementData', $statement->toS2Data() );
    } else {
      $this->add( 'statementData', null );
    }

    // if ( empty( $this->options['no-state-after'] ) ) { // unused?
    //   $clientData = $updateResult['client'] ?? null;
    //   $stateAfter = [
    //     $clientData['sda_mandate_remaining'],
    //     $clientData['fia_mandate_remaining'],
    //     $clientData['fia_approved'],
    //     $clientData['fia_mandate'],
    //     $clientData['fia_available'],
    //     $clientData['fia_unused'],
    //   ];  
    //   $this->add( 'stateAfter', $stateAfter );
    // }

    debug_log( $this->data, 'ClientS2Response::generate(), ', 3 ); 

    return $this->data;

  } // generate


  // Control what gets printed
  // by functions like print_r()
  public function __debugInfo()
  {
    return [
      'options' => $this->options,
      'data' => $this->data
    ];
  }  

} // ClientS2Response