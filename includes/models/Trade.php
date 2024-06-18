<?php namespace App\Models;

use stdClass;


class Trade {

  
  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'trades', $view = 'view_trades' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function removeLocalOnlyFields( array $fields ): array
  {
    $localOnlyFields = [ 'id' ];
    $filteredFields = array_filter( $fields, function( $field ) use ( $localOnlyFields ) {
      return !in_array( $field, $localOnlyFields );
    } );
    // Re-index the array to reset the keys
    return array_values( $filteredFields );
  }


  public function getDbColumnNames( $dropLocalFields = false )
  {
    $results = $this->app->db->table( $this->table )->getColumnNames();
    if ( $dropLocalFields ) $results = $this->removeLocalOnlyFields( $results );
    return $results;
  }


  public function createNewTrade()
  {
    $trade = new stdClass();
    $props = $this->getDbColumnNames();
    foreach ( $props as $prop ) $trade->$prop = null;
    return $trade;
  }


  public function getTradeById( $id = 'new' )
  {
    debug_log( $id, 'getTradeById(), id = ', 2 );
    $isEdit = ( $id != 'new' and $id > 0 );
    $result = $isEdit ? $this->app->db->getFirst( $this->table, $id ) : $this->createNewTrade();
    return $result;
  }


  public function getTradeByUid( $uid )
  {
    debug_log( $uid, 'getTccByUid(), uid = ', 2 );
    $result = $this->app->db->table( $this->table )->where( 'trade_id', '=', $uid )->getFirst();
    return $result;
  }


  public function getTradesByUid( array $UIDs )
  {
    debug_log( $UIDs, 'getTradesByUid(), UIDs = ', 2 );
    if ( ! $UIDs ) return [];
    $result = $this->app->db->table( $this->table )->where( 'trade_id', 'IN', $UIDs )->getAll();
    return $result;
  }


  public function getTrades( $options = [] )
  {
    $daysAgo = $options['days'] ?? null;
    $orderby = $options['orderby'] ?? '';
    $accountant = $options['client_accountant'] ?? null;

    $dateField = 'date';

    $query = $this->app->db->table( $this->view );

    if ( $accountant ) {
      $query->where( 'client_accountant', 'LIKE', "$accountant%" );
    }

    if ( $daysAgo ) {
      if ( $daysAgo == 'this-year') {
        $query->where( $dateField, '>=',  date( 'Y-01-01' ) );
      }
      else if ( $daysAgo == 'last-year' ) {
        $query->where( $dateField, '>=',  date( 'Y-01-01', strtotime( '-1 year' ) ) );
        $query->where( $dateField, '<',   date( 'Y-01-01' ) );
      }
      else if ( $daysAgo == 'this-month' ) {
        $query->where( $dateField, '>=',  date( 'Y-m-01' ) );
      }
      else if ( $daysAgo == 'last-month' ) {
        $query->where( $dateField, '>=',  date( 'Y-m-01', strtotime( '-1 month' ) ) );
        $query->where( $dateField, '<',   date( 'Y-m-01' ) );
      }
      else if ( $daysAgo == 'this-week' ) {
        $query->where( $dateField, '>=',  date( 'Y-m-d', strtotime( 'monday this week' ) ) );
      }
      else if ( $daysAgo == 'last-week' ) {
        $query->where( $dateField, '>=',  date( 'Y-m-d', strtotime( 'monday last week' ) ) );
        $query->where( $dateField, '<',   date( 'Y-m-d', strtotime( 'monday this week' ) ) );
      }
      else if ( $daysAgo == 'today' ) {
        $query->where( $dateField, '>=',  date( 'Y-m-d' ) );
      }
      else {
        $daysAgoDateStr = date( 'Y-m-d', strtotime( '-' . $daysAgo . ' days' ) );
        $query->where( $dateField, '>=',  $daysAgoDateStr );
      }
    }

    $query->orderBy( $orderby ?: $dateField . ' DESC' );

    // debug_dump($query->sql);
    return $query->getAll();    
  } // getTrades


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
    $trade = $this->app->db->getFirst( $this->table, $id );
    if ( ! $trade ) throw new Exception( 'Trade to delete not found.' );
    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $trade = $this->app->db->getFirst( $this->table, $id );
    if ( ! $trade ) throw new Exception( 'Trade to delete not found.' );
    return $this->app->db->table( $this->table )->delete( $id );
  }


  public function save( array $data, $options = [] )
  {
    $options['autoStamp'] = $options['autoStamp'] ?? true;
    $user = $options['user'] ?? $this->app->user;
    $options['user'] = $user->user_id;
    $result = $this->app->db->table( $this->table )->save( $data, $options );
    return $result;
  } 

} // Trade


// `id` int(11) NOT NULL AUTO_INCREMENT,
// `trade_id` varchar(20) DEFAULT NULL,
// `date` date DEFAULT NULL,
// `forex` enum('Capitec','Investec','Mercantile') DEFAULT NULL,
// `forex_reference` varchar(20) DEFAULT NULL COMMENT 'Mercantile',
// `otc` enum('OVEX','VALR') DEFAULT NULL,
// `otc_reference` varchar(20) DEFAULT NULL,
// `client_id` varchar(20) DEFAULT NULL,
// `sda_fia` varchar(10) DEFAULT NULL,
// `zar_sent` decimal(15,2) DEFAULT NULL,
// `usd_bought` decimal(15,2) DEFAULT NULL,
// `trade_fee` decimal(5,2) DEFAULT NULL,
// `forex_rate` decimal(6,3) DEFAULT NULL,
// `zar_profit` decimal(15,2) DEFAULT NULL,
// `percent_return` decimal(5,2) DEFAULT NULL,
// `fee_category_percent_profit` decimal(5,2) DEFAULT NULL,
// `recon_id1` varchar(20) DEFAULT NULL,
// `recon_id2` varchar(20) DEFAULT NULL,
// `amount_covered` decimal(15,2) DEFAULT NULL,
// `allocated_pins` text DEFAULT NULL,
// `created_at` datetime NOT NULL DEFAULT current_timestamp(),
// `created_by` varchar(20) NOT NULL DEFAULT '_system_',
// `updated_at` datetime DEFAULT NULL,
// `updated_by` varchar(20) DEFAULT NULL,
// `deleted_at` datetime DEFAULT NULL,
// `deleted_by` varchar(20) DEFAULT NULL,