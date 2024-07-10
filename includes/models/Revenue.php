<?php namespace App\Models;


use Exception;
use stdClass;

class Revenue
{
  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'ch_revenue_models', $view = 'view_revenue_models' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewRevenue(): stdClass
  {
    $revenue = new stdClass;
    $revenue->id = null;
    $revenue->name = null;
    $revenue->type_id = null;
    $revenue->client_id = null;
    $revenue->referrer_id = null;
    return $revenue;
  }


  public function getRevenueById( $id = 'new' ): stdClass
  {
    debug_log( $id, 'getRevenue(), id = ', 2 );
    $isNew = ( $id === 'new' || $id < 0 || ! $id );
    return $isNew ? $this->createNewRevenue() : $this->app->db->getFirst( $this->table, $id );
  }


  public function getRevenueByUid( $ID )
  {
    debug_log( $ID, 'getRevenueByUid(), ID = ', 2 );
    return $this->app->db->table( $this->table )->where( 'id', $ID )->getFirst();
  }


  public function save( array $data, $options = [] )
  {
    $options['autoStamp'] = $options['autoStamp'] ?? true;
    $user = $options['user'] ?? $this->app->user;
    $options['user'] = $user->user_id;
    $result = $this->app->db->table( $this->table )->save( $data, $options );
    return $result;
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
    $revenue = $this->app->db->getFirst( $this->table, $id );
    if ( ! $revenue ) throw new Exception( 'Revenue to delete not found.' );
    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $revenue = $this->app->db->getFirst( $this->table, $id );
    if ( ! $revenue ) throw new Exception( 'Revenue to delete not found.' );
    return $this->app->db->table( $this->table )->delete( $id );
  }

}