<?php namespace App\Models;

use stdClass;
use Exception;


class Client {

  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'clients', $view = 'view_clients' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewClient()
  {
    $client = new stdClass();
    $props = $this->getDbColumnNames();
    foreach ( $props as $prop ) $client->{$prop} = null;
    $client->client_id = substr( uniqid(), -8 );
    $client->trader_id = 'chmarkts'; // Temp?
    debug_log( $client, 'createNewClient(), client: ', 2 );
    return $client;
  }


  public function getDbColumnNames() {
    $results = $this->app->db->table( 'clients' )->getColumnNames();
    return $results;
  }


  public function resolveAccountantName( $name )
  {
    if ( $name == 'Vitalis Accountant' ) return 'Vitalis';
    if ( $name == 'Seth Meyerowitz' ) return 'Seth';
    if ( $name == 'Daniel Fourie'   ) return 'Daniel';
    if ( $name == 'SBJ Loans'       ) return 'SBJ';
    if ( $name == 'All'             ) return;
    return $name;
  }


  public function getAllByAccountant( $accountant = null, $options = [] )
  {
    $query = $this->app->db->table( 'clients' );

    if ( $accountant and $accountant != 'All')
    {
      $clientAccountant = $this->resolveAccountantName( $accountant );
      if ( $clientAccountant != $accountant ) {
        $query->where( [ [ 'accountant', '=', $clientAccountant ],
          [ 'accountant', '=', $accountant, 'OR' ] ] );
      } else {
        $query->where( 'accountant', '=', $clientAccountant );
      }
    }

    $status = $options['status'] ?? null;
    if ( $status === 'All' ) $status = null;
    if ( $status === 'Deleted' ) {
      $query->where( 'deleted_at', 'IS NOT', null );
      $status = null;
    }    
    else $query->where( 'deleted_at', 'IS', null );
    if ( $status ) $query->where( 'status', '=', $status );

    $except = $options['except'] ?? null;
    if ( $except ) $query->where( 'id', '!=', $except );

    return $query->orderBy( 'name asc' )->getAll();
  }


  public function getAllByReferrer( $referrer_id )
  {
    debug_log( $referrer_id, 'getAllByReferrer(), referrer_id = ', 2 );
    return $this->app->db->table( $this->table )
      ->where( 'referrer_id', '=', $referrer_id )->getAll();
  }


  public function getClientIdsByUid( $ignoreDeleted = 'yes' )
  {
    $query = $this->app->db->table( $this->table );
    if ( $ignoreDeleted ) $query->where( 'deleted_at', 'IS', null );
    // A lookup hash is much faster to search than a plain array of objects.
    return $query->getLookupBy( 'client_id', 'id' );    
  }


  public function getClientByUid( $uid )
  {
    debug_log( $uid, 'getClientByUid(), uid = ', 2 );
    $client = $this->app->db->primaryKey( 'client_id' )->getFirst( $this->table, $uid );
    return $client;
  }


  public function getClientById( $id = 'new' )
  {
    debug_log( $id, 'getClientById(), id = ', 2 );
    $isNew = ( $id === 'new' || $id < 0 || ! $id );
    $result = $isNew ? $this->createNewClient() : $this->app->db->getFirst( $this->table, $id );
    return $result;
  }


  public function getPendingTccs( $clientUid, $fromYear = null )
  {
    $query = $this->app->db->table( 'tccs' )->where( 'deleted_at', 'IS', null )
      ->where( 'client_id', '=', $clientUid )
      ->where( 'status', '=', 'Pending' );
    if ( $fromYear ) $query->where( 'YEAR(application_date)', '>=', $fromYear );
    $results = $query->getAll();
    return $results;
  }


  public function getDeclinedTccs( $clientUid, $fromYear = null )
  {
    $query = $this->app->db->table( 'tccs' )->where( 'deleted_at', 'IS', null )
      ->where( 'client_id', '=', $clientUid )
      ->where( 'status', '=', 'Declined' );
    if ( $fromYear ) $query->where( 'YEAR(`date`)', '>=', $fromYear );
    $results = $query->getAll();
    return $results;
  }


  // NOTE: TCC allocations before 2023 were not tracked and we need
  // to look at and trust the `rollover` column value to determine which 2022
  // TCCs were not fully spent and had amounts remaining to use in 2023.
  public function getClientTccPins( $client, $options = [] )
  {
    $clientId = is_string( $client ) ? $client : $client->client_id;

    $theYear = $options['year'] ?? date( 'Y' );
    $fromDate = $options['from'] ?? "$theYear-01-01";
    $toDate = $options['to'] ?? "$theYear-12-31";
    $type = $options['type'] ?? 'All';

    debug_log( PHP_EOL, '', 2 );
    debug_log( [ 'type' => $type, 'year' => $theYear, 'from' => $fromDate, 'to' => $toDate ], 
      'getClientTccPins(), ', 2 );

    $sql = 'SELECT * FROM tccs WHERE deleted_at IS NULL AND client_id = ?';

    if ( $type == 'All' )
    {
      $sql .= ' AND ( ( `date` IS NULL AND application_date BETWEEN ? AND ? ) OR ' .
       '( `date` BETWEEN ? AND ? ) ) ORDER BY `date`, application_date, created_at';
      $params = [ $clientId, $fromDate, $toDate, $fromDate, $toDate ];
    }

    else if ( $type == 'AffectsState' )
    {
      $sql .= ' AND ( ( status = "Approved" AND YEAR(`date`) = ? )' .
        ' OR ( rollover > 0 AND YEAR(`date`) = ? ) ) ORDER BY `date`, created_at';
      $params = [ $clientId, $theYear, (int) $theYear - 1 ];
    }

    else if ( $type == 'ClientDetailView' )
    {
      $sql .= ' AND ( status IN ("Approved","Pending","Awaiting Docs") OR YEAR(`date`) = ? OR YEAR(application_date) = ?' .
        ' OR ( rollover > 0 AND YEAR(`date`) = ? ) ) ORDER BY `date`, created_at';
      $params = [ $clientId, $theYear, $theYear, (int) $theYear - 1 ];
    }

    else
    {
      throw new Exception( 'Invalid type: ' . $type );
    }

    return $this->app->db->query( $sql, $params );
  }


  public function getClientTrades( $client, $options = [] )
  {
    $clientId = is_string( $client ) ? $client : $client->client_id;

    $theYear = $options['year'] ?? date( 'Y' );
    $fromDate = $options['from'] ?? "$theYear-01-01";
    $toDate = $options['to'] ?? "$theYear-12-31";
    $type = $options['type'] ?? 'All';

    debug_log( PHP_EOL, '', 2 );
    debug_log( $type, 'getClientTrades(), type = ', 2 );
    debug_log( $theYear, 'Year: ', 2 );
    debug_log( $fromDate, 'From Date: ', 2 );
    debug_log( $toDate, 'To Date: ', 2 );


    $sql = 'SELECT * FROM trades WHERE deleted_at IS NULL AND client_id = ?';

    if ( $type == 'All' ) {
      $sql .= ' AND `date` BETWEEN ? AND ? ORDER BY `date`, created_at'; 
      $params = [ $clientId, $fromDate, $toDate ];
    }

    else {
      throw new Exception( 'Invalid type: ' . $type );
    }
      
    return $this->app->db->query( $sql, $params );
  }


  public function getAnnualInfo( $client, $year = null )
  {
    debug_log( $year, 'Client::getAnnualInfo(), year = ', 2 );

    if ( ! $year ) $year = date( 'Y' );

    $annualInfo = $this->app->db->table( 'clients_annual_info' )
       ->where( 'client_id', $client->id )
       ->where( 'year', $year )
       ->getFirst();

    if ( ! $annualInfo ) {
      $annualInfo = new stdClass();
      $annualInfo->year = $year;
      $annualInfo->client_id = $client->id;
      $annualInfo->trading_capital = $client->trading_capital;
      $annualInfo->fia_mandate = $client->fia_mandate;
      $annualInfo->sda_mandate = $client->sda_mandate;
    }

    return $annualInfo;
  }


  public function unSoftDelete( $id, $user = null )
  {
    $user = $user ?: $this->app->user;
    $data = [ 'id' => $id, 'deleted_at' => null, 'deleted_by' => null ];
    return $this->app->db->table( $this->table )->update( $data,
      [ 'autoStamp' => true, 'user' => $user->user_id ] );
  }  


  public function softDelete( $id, $user = null )
  {
    $user = $user ?: $this->app->user;

    $client = $this->app->db->getFirst( $this->table, $id );
    if ( ! $client ) throw new Exception( 'Client to delete not found.' );

    if ( $client->status == 'Active' )
      throw new Exception( 'Unable to delete Active Clients.' );

    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }  


  public function delete( $id )
  {
    $client = $this->app->db->getFirst( $this->table, $id );
    if ( ! $client ) throw new Exception( 'Client to delete not found.' );

    if ( $client->status == 'Active' )
      throw new Exception( 'Unable to permantly delete Active Clients.' );

    $clientPath = $client->name . '_' . $client->id;
    $clientDir = $this->app->uploadsDir . __DS__ . $clientPath;

    // Backup marriage_cert file before deleting the record
    $marriageCert = $client->spare_1; // TODO: Remember to update this mapping when we change the column name
    if ( $marriageCert ) {     
      $marriageCertFile = $clientDir . __DS__ . $marriageCert;
      $backupFile = $marriageCertFile . '_del_' . time() . '.bak';
      rename( $marriageCertFile, $backupFile );
      debug_log( $backupFile, 'Client::delete(), Created backup: ', 2 );
    }

    // Backup crypto_declaration file before deleting the record
    $cryptoDeclaration = $client->spare_2; // TODO: Remember to update this mapping when we change the column name
    if ( $cryptoDeclaration ) {     
      $cryptoDeclarationFile = $clientDir . __DS__ . $cryptoDeclaration;
      $backupFile = $cryptoDeclarationFile . '_del_' . time() . '.bak';
      rename( $cryptoDeclarationFile, $backupFile );
      debug_log( $backupFile, 'Client::delete(), Created backup: ', 2 );
    }

    return $this->app->db->table( $this->table )->delete( $id );
  }

} // Client
