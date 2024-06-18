<?php namespace App\Models;

use stdClass;
use Exception;


class Tcc {

  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'tccs', $view = 'view_tccs' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewTcc()
  {
    $tcc = new stdClass();
    $props = $this->getDbColumnNames();
    foreach ( $props as $prop ) $tcc->$prop = null;
    $tcc->tcc_id = $this->generateUUID();
    $tcc->status = 'Pending';
    return $tcc;
  }


  /**
   * This function generates a valid UUID v4 string that's always 36 characters long.
   */
  public function generateUUID()
  {
    if (function_exists('com_create_guid') === true) {
      return trim(com_create_guid(), '{}');
    }
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), // 8 digits
      mt_rand(0, 0xffff), // 4 digits
      mt_rand(0, 0x0fff) | 0x4000, // 4 digits, 13th character is "4"
      mt_rand(0, 0x3fff) | 0x8000, // 4 digits, 17th character is "8", "9", "A", or "B"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff) // 12 digits
    );
  }


  public function getDbColumnNames( $dropLocalFields = false ) {
    $results = $this->app->db->table( $this->table )->getColumnNames();
    if ( $dropLocalFields ) $results = $this->removeLocalOnlyFields( $results );
    return $results;
  }


  public function removeLocalOnlyFields(array $fields): array
  {
    $localOnlyFields = [ 'id', 'deleted_at', 'deleted_by',
      'sync_at', 'sync_by', 'sync_from', 'sync_type' ];
    $filteredFields = array_filter($fields, function( $field ) use ( $localOnlyFields ) {
      return !in_array($field, $localOnlyFields);
    } );
    // Re-index the array to reset the keys
    return array_values( $filteredFields );
  }  


  public function resolveAccountantName( $name )
  {
    if ( $name == 'Vitalis Accountant' ) return 'Vitalis';
    if ( $name == 'Seth Meyerowitz' ) return 'Seth';
    if ( $name == 'Daniel Fourie'   ) return 'Daniel';
    if ( $name == 'SBJ Loans'       ) return 'SBJ';
    return $name;
  }


  public function mapCategoryToDateField( $category ) {
    if ( $category == 'Approved'  ) return 'date';
    if ( $category == 'Declined'  ) return 'date';
    if ( $category == 'Expired'   ) return 'date';
    if ( $category == 'Used'      ) return 'date';
    if ( $category == 'Updated'   ) return 'updated_at';
    if ( $category == 'Deleted'   ) return 'deleted_at';
    return 'created_at';
  }


  public function mapCategoryToStatus( $category ) {
    if ( $category == 'Awaiting Docs' ) return 'Awaiting Docs';
    if ( $category == 'Pending'       ) return 'Pending';
    if ( $category == 'Approved'      ) return 'Approved';
    if ( $category == 'Declined'      ) return 'Declined';
    if ( $category == 'Expired'       ) return 'Expired';
    if ( $category == 'Used'          ) return 'Used';
    return null;
  }


  public function removeTradeFromAllocs( $pin, $trade, $options = [] )
  {
    debug_log( $pin, 'TccModel::removeTradeFromAllocs(), pin = ', 2 );
    $tcc = is_object( $pin ) ? $pin : $this->getTccByPin( $pin );
    if ( ! $tcc ) {
      debug_log( $pin, 'TccModel::removeTradeFromAllocs(), TCC not found for pin = ' );
      return;
    }
    $allocs = $tcc->allocated_trades ? json_decode( $tcc->allocated_trades, true ) : null;
    if ( ! $allocs ) return;
    $coverAmount = $allocs[ $trade->trade_id ] ?? 0;
    unset( $allocs[ $trade->trade_id ] );
    $tcc->allocated_trades = json_stringify( $allocs );
    $tcc->amount_used = floatval( $tcc->amount_used ) - $coverAmount;
    $tcc->amount_remaining = floatval( $tcc->amount_remaining ) + $coverAmount;
    $tcc->amount_available = $tcc->status == 'Approved' ? $tcc->amount_remaining : 0;
    debug_log( $tcc, 'TccModel::removeTradeFromAllocs(), tcc before save = ', 2 );
    return $this->save( (array) $tcc, $options );
  }


  public function getTccById( $id = 0 )
  {
    debug_log( $id, 'getTccById(), id = ', 2 );
    $isEdit = ( $id != 'new' and $id > 0 );
    $result = $isEdit ? $this->app->db->getFirst( $this->view, $id ) : $this->createNewTcc();
    return $result;
  }


  public function getTccsByPin( array $pins )
  {
    debug_log( $pins, 'getTccsByPin(), pins = ', 2 );
    if ( ! $pins ) return [];
    $result = $this->app->db->table( $this->table )->where( 'tcc_pin', 'IN', $pins )
      ->orderBy('`date`, created_at')->getAll();
    return $result;
  }  


  public function getTccByPin( $pinNo )
  {
    debug_log( $pinNo, 'getTccByPin(), pinNo = ', 2 );
    $result = $this->app->db->table( $this->table )->where( 'tcc_pin', '=', $pinNo )->getFirst();
    return $result;
  }


  public function getTccIdsByUid( $ignoreDeleted = 'yes' )
  {
    $query = $this->app->db->table( $this->table );
    if ( $ignoreDeleted ) $query->where( 'deleted_at', 'IS', null );
    // A lookup hash is much faster to search than a plain array of objects.
    return $query->getLookupBy( 'tcc_id', 'id' );
  }


  public function getTccByTaxCaseNo( $caseNo )
  {
    debug_log( $caseNo, 'getTccByTaxCaseNo(), caseNo = ', 2 );
    $result = $this->app->db->table( $this->table )->where( 'tax_case_no', '=', $caseNo )->getFirst();
    return $result;
  }


  public function getAllByAccountant( $accountant = null, $options = [] )
  {
    $daysAgo = isset( $options['days'] ) ? $options['days'] : null;
    $search  = isset( $options['search'] ) ? $options['search'] : '';
    $category = isset( $options['category'] ) ? $options['category'] : '';
    $orderby = isset( $options['orderby'] ) ? $options['orderby'] : '';

    $status = $this->mapCategoryToStatus( $category );
    $dateField = $this->mapCategoryToDateField( $category );

    $query = $this->app->db->table( $this->view );

    if ( $accountant and $accountant != 'All')
    {
      $clientAccountant = $this->resolveAccountantName( $accountant );
      if ( $clientAccountant != $accountant ) {
        $query->where( [ [ 'client_accountant', '=', $clientAccountant ], 
          [ 'client_accountant', '=', $accountant, 'OR' ] ] );
      } else {
        $query->where( 'client_accountant', '=', $clientAccountant );
      }
    }

    if ( $category != 'Deleted' ) $query->where( 'deleted_at', 'IS', null );

    if ( $status  ) is_array( $status )
      ? $query->where( 'status', 'IN', $status )
      : $query->where( 'status', '=', $status );

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

    if ( $search  ) $query->where( 'client_name', 'like', '%' . $search . '%' );

    $query->orderBy( $orderby ?: $dateField . ' DESC' );

    // debug_dump($query->sql);
    return $query->getAll();
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

    $tcc = $this->app->db->getFirst( $this->table, $id );
    if ( ! $tcc ) throw new Exception( 'TCC to delete not found.' );

    if ( $tcc->status == 'Approved' || $tcc->status == 'Expired' )
      throw new Exception( 'Unable to delete Approved or Expired TCCs.' );

    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $tcc = $this->app->db->getFirst( $this->view, $id );
    if ( ! $tcc ) throw new Exception( 'TCC to delete not found.' );

    if ( $tcc->status == 'Approved' || $tcc->status == 'Expired' )
      throw new Exception( 'Unable to permantly delete Approved or Expired TCCs.' );

    $certFile = $this->app->uploadsDir . __DS__ . $tcc->client_name . '_' . 
      $tcc->client_id2 . __DS__ . $tcc->tax_cert_pdf;

    // Backup tax_cert_pdf file before deleting the record
    if ( $certFile and file_exists( $certFile ) ) {
      $backupFile = $certFile . '_del_' . time() . '.bak';
      rename( $certFile, $backupFile );
      debug_log( $backupFile, 'Tcc::delete(), Backed up: ', 2 );
    }

    return $this->app->db->table( $this->table )->delete( $id );
  }


  public function update( array $data, $options = [] )
  {
    $options['autoStamp'] = $options['autoStamp'] ?? true;
    $user = $options['user'] ?? $this->app->user;
    $options['user'] = $user->user_id;
    $result = $this->app->db->table( $this->table )->update( $data, $options );
    return $result;
  }


  public function save( array $data, $options = [] )
  {
    $options['autoStamp'] = $options['autoStamp'] ?? true;
    $user = $options['user'] ?? $this->app->user;
    $options['user'] = $user->user_id;
    $result = $this->app->db->table( $this->table )->save( $data, $options );
    return $result;
  }  

} // Tcc