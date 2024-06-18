<?php namespace App\Services;

use F1\Logger;

/**
 * App Logger Class - 25 Nov 2023
 * 
 * @author Neels Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.4 - DEV - 28 Feb 2024
 *  - Add support for CLI requests.
 *
 */

class AppLogger extends Logger {

  public $fileSize;
  public $isNewLog;

  public function __construct( $app, $logsDir = null, $logFile = null ) {
    $dateParts = explode( '-', $app->today );
    $logsDir = $logsDir ?: $app->storageDir . __DS__ . 'logs';
    if ( ! $logFile ) {
      if ( isset( $app->security ) and $app->security->isLoggedIn() ) {
        $logsDir .= __DS__ . $app->request->module;
        $logFile = 'u' . $app->security->getUserId() . '_' . $app->today;
      } else { 
        $logsDir .= __DS__ . ( php_sapi_name() == 'cli' ? 'cli' : 'guest' );
        $logFile = 'log' . '_' . $app->today;
      }
      if ( __DEBUG__ >= 2 ) $logFile .= '_' . date( 'H' );
      $logFile .= '.txt';
    }
    $logsDir .= __DS__ . $dateParts[0] . __DS__ . $dateParts[1] . __DS__ . $dateParts[2];
    parent::__construct( $logsDir, $logFile, __DEBUG__ );
    $this->fileSize = $this->getFileSize();
    $this->isNewLog = $this->fileSize < 250;
  }

}