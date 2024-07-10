<?php namespace App\Models;


use Exception;
use stdClass;

class ModelBeneficiary
{
  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'ch_revenue_model_beneficiaries', $view = 'view_revenue_model_beneficiaries' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewModelBeneficiaries(): stdClass
  {
    $model = new stdClass;
    $model->id = null;
    $model->revenue_model_id = null;
    $model->beneficiary_id = null;
    $model->revenue_share = null;
    return $model;
  }


  public function getModelBeneficiariesById( $id = 'new' ): stdClass
  {
    debug_log( $id, 'getModelBeneficiaries(), id = ', 2 );
    $isNew = ( $id === 'new' || $id < 0 || ! $id );
    return $isNew ? $this->createNewModelBeneficiaries() : $this->app->db->getFirst( $this->table, $id );
  }

  public function getAllByModelBeneficiariesType( $id )
  {
    debug_log( $id, 'getAllByModelBeneficiariesType(), id = ', 2 );
    return $this->app->db->table( $this->table )
      ->where( 'id', '=', $id )->getAll();
  }


  public function getModelBeneficiariesByUid( $ID )
  {
    debug_log( $ID, 'getModelBeneficiariesByUid(), ID = ', 2 );
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
    $model_beneficiaries = $this->app->db->getFirst( $this->table, $id );
    if ( ! $model_beneficiaries ) throw new Exception( 'Model Beneficiaries to delete not found.' );
    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $model_beneficiaries = $this->app->db->getFirst( $this->table, $id );
    if ( ! $model_beneficiaries ) throw new Exception( 'Model Beneficiaries to delete not found.' );
    return $this->app->db->table( $this->table )->delete( $id );
  }

}