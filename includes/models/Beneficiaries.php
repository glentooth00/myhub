<?php namespace App\Models;


use Exception;
use stdClass;

class Beneficiaries
{
  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'ch_beneficiaries', $view = 'view_beneficiaries' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewBeneficiaries(): stdClass
  {
    $beneficiaries = new stdClass;
    $beneficiaries->id = null;
    $beneficiaries->name = null;
    $beneficiaries->type_id = null;
    $beneficiaries->referrer_id = substr( uniqid(), -8 );
    $beneficiaries->created_at = null;
    $beneficiaries->deleted_at = null;
    return $beneficiaries;
  }


  public function getBeneficiariesById( $id = 'new' ): stdClass
  {
    debug_log( $id, 'getBeneficiaries(), id = ', 2 );
    $isNew = ( $id === 'new' || $id < 0 || ! $id );
    return $isNew ? $this->createNewBeneficiaries() : $this->app->db->getFirst( $this->table, $id );
  }

  public function getAllByBeneficiariesType( $id )
  {
    debug_log( $id, 'getAllByBeneficiariesType(), id = ', 2 );
    return $this->app->db->table( $this->table )
      ->where( 'id', '=', $id )->getAll();
  }


  public function getBeneficiariesByUid( $ID )
  {
    debug_log( $ID, 'getBeneficiariesByUid(), ID = ', 2 );
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
    $beneficiaries = $this->app->db->getFirst( $this->table, $id );
    if ( ! $beneficiaries ) throw new Exception( 'Beneficiaries to delete not found.' );
    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $beneficiaries = $this->app->db->getFirst( $this->table, $id );
    if ( ! $beneficiaries ) throw new Exception( 'Beneficiaries to delete not found.' );
    return $this->app->db->table( $this->table )->delete( $id );
  }

}