<?php namespace App\Models;

use stdClass;
use Exception;


class Task {

  private $app;


  public function __construct( $app = null )
  {
    $this->app = $app;
  }


  public function submitTask( $task, $options = [] )
  {
    debug_log( $options, 'Task::submitTask(), ' . $task . ': ' );
    $user = $this->app->user;
    $status = isset( $options['status'] ) ? $options['status'] : 'pending';
    $job_id = isset( $options['jobId'] ) ? $options['jobId'] : null;
    $startTime = isset( $options['time'] ) ? $options['time'] : null;
    $progress = isset( $options['progress'] ) ? $options['progress'] : 0;
    $progressMessage = isset( $options['progressMessage'] ) ? $options['progressMessage'] : '';
    $maxRetries = isset( $options['maxRetries'] ) ? $options['maxRetries'] : 0;
    $expiresAfter = isset( $options['expiresAfter'] ) ? $options['expiresAfter'] : 60 * 24; // 1 day in minutes
    $expiresAt = date( 'Y-m-d H:i:s', strtotime( $startTime ) + $expiresAfter * 60 );

    $task = [
      'status' => $status,
      'job_desc' => $task,
      'progress' => $progress,
      'expires_after' => $expiresAfter,
      'expires_at' => $expiresAt,
    ];

    if ( $job_id ) $task['job_id'] = $job_id;
    if ( $startTime ) $task['start_time'] = $startTime;
    if ( $progressMessage ) $task['progress_message'] = $progressMessage;
    if ( $maxRetries ) $task['max_retries'] = $maxRetries;

    // With "autoStamp" option, the model will automatically set the "updated_at" and "updated_by" fields.
    // The "updated_by" field will be set to the current user's uid: $user->user_id.
    $uid = $user->user_id;
    $result = $this->app->db->table( 'tasks' )->save( $task, [ 'autoStamp' => true, 'user' => $uid ] );
    if ( ! $result or $result['status'] != 'inserted' ) throw new Exception( 'Failed to submit task.' );

    return $this->app->db->getFirst( 'tasks', $result['id'] );
  }


  public function getNextTask( $user )
  {
    debug_log( 'Task::getNextTask()' );

    $db = $this->app->db;
    $db->pdo->beginTransaction();

    try {

      $task = $db->table( 'tasks' )
        ->where( 'status', '=', 'pending' )
        ->orWhere( [ 'status', '=', 'failed' ], [ 'retries', '<', 'max_retries', 'AND' ] )
        ->orderBy( 'created_at' )
        ->getFirst();
    
      // Re-submit a failed task if it has not reached max retries yet.
      if ( $task and $task->status === 'failed' and $task->retries < $task->max_retries ) {
        $uid = $user->user_id;
        $taskData = (array) $task;
        $taskData['status'] = 'running';
        $taskData['start_time'] = date( 'Y-m-d H:i:s' );
        $taskData['retries'] = $taskData['retries'] + 1;
        $db->table( 'tasks' )->insert( $taskData, [ 'autoStamp' => true, 'user' => $uid ] );
      } 
      
      $db->pdo->commit();
      
      return $task ? $task['task'] : null;
    } catch ( Exception $e ) {
      $db->pdo->rollback();
      throw $e;
    }
  }


  public function getLastTaskByJobId( $jobId )
  {
    $task = $this->app->db->table( 'tasks' )
      ->where( 'job_id', '=', $jobId )
      ->orderBy( 'created_at DESC' )->getFirst();

    return $task;
  }


  public function updateProgress( $task, $progress, $progressMessage = null )
  {
    debug_log( $progress, 'Task::updateProgress(), ' . $task->job_desc . ': ' );
    $task->progress = $progress;
    $user = $this->app->user->user_id;
    if ( $progressMessage ) $task->progress_message = $progressMessage;
    $result = $this->app->db->table( 'tasks' )->save( (array) $task, 
      [ 'autoStamp' => true, 'user' => $user ] );

    if ( $result['status'] != 'updated' )
      throw new Exception( 'Failed to update task progress.' );
  }


  public function recordTaskCompletion( $task, $result, $success = true )
  {
    debug_log( $result, 'Task::recordTaskCompletion(), ' . $task->job_desc . ': ' );
    $task->result = $result;
    $task->status = $success ? 'completed' : 'failed';
    $task->end_time = date( 'Y-m-d H:i:s' );
    $user = $this->app->user->user_id;
    $result = $this->app->db->table( 'tasks' )->save( (array) $task, 
      [ 'autoStamp' => true, 'user' => $user ] );

    if ( $result['status'] != 'updated' )
      throw new Exception( 'Failed to update task result.' );
  }


  public function deleteExpiredTasks( $db ) {
    debug_log( 'Task::deleteExpiredTasks()' );
    $result = $this->app->db->table( 'tasks' )
      ->where( 'expires_at', '<', date( 'Y-m-d H:i:s' ) )
      ->delete();

    debug_log( "$result task(s) deleted.", 'Task::deleteExpiredTasks(), ' );
  }

} // Task


// CREATE TABLE `tasks` (
//   `id` int(11) NOT NULL AUTO_INCREMENT,
//   `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
//   `job_id` varchar(36) DEFAULT NULL,
//   `job_desc` varchar(255) DEFAULT NULL,
//   `progress` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '0 - 100 %',
//   `progress_message` varchar(255) DEFAULT NULL,
//   `start_time` datetime DEFAULT NULL,
//   `end_time` datetime DEFAULT NULL,
//   `max_retries` int(11) NOT NULL DEFAULT '1',
//   `retries` int(11) NOT NULL DEFAULT '0',
//   `expires_after` int(11) DEFAULT '60' COMMENT 'Minutes',
//   `expires_at` datetime DEFAULT NULL,
//   `updated_at` datetime DEFAULT NULL,
//   `updated_by` varchar(20) DEFAULT NULL,
//   `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
//   `created_by` varchar(20) NOT NULL DEFAULT '_cron_',
//   `result` varchar(255) NOT NULL,
//   PRIMARY KEY (`id`),
//   UNIQUE KEY `job_no` (`job_no`)
// )