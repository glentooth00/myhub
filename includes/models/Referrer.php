<?php namespace App\Models;


use Exception;
use stdClass;

class Referrer
{
  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'ch_referrers', $view = 'view_referrers' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewReferrer(): stdClass
  {
    $referrer = new stdClass;
    $referrer->id = null;
    $referrer->type_id = null;
    $referrer->referrer_id = substr( uniqid(), -8 );
    $referrer->name = null;
    $referrer->email = null;
    $referrer->notes = null;
    $referrer->id_number = null;
    $referrer->phone_number = null;
    $referrer->user_id = $referrer->referrer_id;
    $referrer->client_id = null;
    $referrer->created_at = null;
    $referrer->updated_at = null;
    $referrer->updated_by = null;
    return $referrer;
  }


  public function getReferrerById( $id = 'new' ): stdClass
  {
    debug_log( $id, 'getReferrer(), id = ', 2 );
    $isNew = ( $id === 'new' || $id < 0 || ! $id );
    return $isNew ? $this->createNewReferrer() : $this->app->db->getFirst( $this->table, $id );
  }


  public function getReferrerByUid( $UID )
  {
    debug_log( $UID, 'getReferrerByUid(), UID = ', 2 );
    return $this->app->db->table( $this->table )->where( 'referrer_id', $UID )->getFirst();
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
    $referrer = $this->app->db->getFirst( $this->table, $id );
    if ( ! $referrer ) throw new Exception( 'Referrer to delete not found.' );
    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $referrer = $this->app->db->getFirst( $this->table, $id );
    if ( ! $referrer ) throw new Exception( 'Referrer to delete not found.' );
    return $this->app->db->table( $this->table )->delete( $id );
  }

}