<?php namespace App\Models;

use stdClass;
use Exception;

use App\Exceptions\ValidationException;


class Ncr {

  private $app;

  public $table;
  public $view;


  function __construct( $app, $table = 'ch_ncrs', $view = 'view_ncrs' )
  {
    $this->app = $app;
    $this->table = $table;
    $this->view = $view;
  }


  public function getNewNcr()
  {
    $ncr = new stdClass();
    $ncr->id = null;
    $ncr->name = null;
    $ncr->created_at = null;
    $ncr->created_by = null;
    $ncr->updated_at = null;
    $ncr->updated_by = null;
    return $ncr;
  }


  public function getNcrById( $id = 'new' )
  {
    debug_log( $id, 'getNcrById(), id = ', 2 );
    $isEdit = ( $id !== 'new' and $id > 0 );
    $result = $isEdit ? $this->app->db->getFirst( $this->table, $id ) : $this->getNewNcr();
    return $result;
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

} // Ncr
