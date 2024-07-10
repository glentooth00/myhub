<?php namespace App\Models;


use Exception;
use stdClass;

class BeneficiariesType
{
  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'ch_beneficiaries_types', $view = 'view_beneficiaries_types' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewBeneficiariesType(): stdClass
  {
    $beneficiaries_types = new stdClass;
    $beneficiaries_types->id = null;
    $beneficiaries_types->name = null;
    return $beneficiaries_types;
  }


  public function getBeneficiariesTypeById( $id = 'new' ): stdClass
  {
    debug_log( $id, 'getBeneficiariesType(), id = ', 2 );
    $isNew = ( $id === 'new' || $id < 0 || ! $id );
    return $isNew ? $this->createNewBeneficiariesType() : $this->app->db->getFirst( $this->table, $id );
  }


  public function getBeneficiariesTypeByUid( $ID )
  {
    debug_log( $ID, 'getBeneficiariesTypeByUid(), ID = ', 2 );
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
    $beneficiaries_types = $this->app->db->getFirst( $this->table, $id );
    if ( ! $beneficiaries_types ) throw new Exception( 'Beneficiaries Types to delete not found.' );
    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $beneficiaries_types = $this->app->db->getFirst( $this->table, $id );
    if ( ! $beneficiaries_types ) throw new Exception( 'Beneficiaries Type to delete not found.' );
    return $this->app->db->table( $this->table )->delete( $id );
  }

} 