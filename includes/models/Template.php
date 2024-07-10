<?php namespace App\Models;


use Exception;
use stdClass;

class Template
{
  private $app;

  public $table;
  public $view;


  public function __construct( $app, $table = 'ch_revenue_model_templates', $view = 'view_revenue_model_templates' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function createNewTemplate(): stdClass
  {
    $template = new stdClass;
    $template->id = null;
    $template->name = null;
    $template->model_type_id = null;
    return $template;
  }


  public function getTemplateById( $id = 'new' ): stdClass
  {
    debug_log( $id, 'getTemplate(), id = ', 2 );
    $isNew = ( $id === 'new' || $id < 0 || ! $id );
    return $isNew ? $this->createNewTemplate() : $this->app->db->getFirst( $this->table, $id );
  }


  public function getTemplateByUid( $ID )
  {
    debug_log( $ID, 'getTemplateByUid(), ID = ', 2 );
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
    $template = $this->app->db->getFirst( $this->table, $id );
    if ( ! $template ) throw new Exception( 'Template to delete not found.' );
    $data = [ 'id' => $id, 'deleted_at' => date( 'Y-m-d H:i:s' ), 'deleted_by' => $user->user_id ];
    return $this->app->db->table( $this->table )->update( $data );
  }


  public function delete( $id )
  {
    $template = $this->app->db->getFirst( $this->table, $id );
    if ( ! $template ) throw new Exception( 'Template to delete not found.' );
    return $this->app->db->table( $this->table )->delete( $id );
  }

}