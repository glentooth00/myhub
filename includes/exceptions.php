<?php /* exceptions.php */

namespace App\Exceptions;

use ErrorException;
use Exception;


set_exception_handler( function ( $ex ) {

  global $app;

  $msg = ( $ex instanceof ErrorException ? '' : 'Uncaught Exception: ' ) . $ex->getMessage();
  $isAjax = ( isset( $app->request ) and $app->request->isAjax and $app->request->type != 'page' );
  $isProd = defined('__ENV_PROD__') ? __ENV_PROD__ : false;

  if ( function_exists( 'abort_uploads' ) ) abort_uploads( 'uncaught_exception_handler' );

  $errorDetails = [
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'time' => date('Y-m-d H:i:s'),
    'message' => $msg . ( $isProd ? '' : ' in ' . $ex->getFile() . 
      ' on line ' . $ex->getLine() . ' code ' . $ex->getCode() ),
  ];

  $debug = defined('__DEBUG__') ? __DEBUG__ : ( $isProd ? 0 : 9 );

  if ( $debug > 3 ) $errorDetails['bootlog'] = $app->bootLog ?? [];
  if ( $debug > 2 ) {
    $errorDetails['session'] = $_SESSION ?? [];
    $errorDetails['trace'] = $ex->getTraceAsString();
  }
  if ( $debug > 1) {
    $errorDetails['agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '-';
    $errorDetails['referer'] = $_SERVER['HTTP_REFERER'] ?? 'none';
    $errorDetails['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'none';
  }

  if ( isset( $app->logger ) ) $app->logger->log( print_r( $errorDetails, true ) . PHP_EOL, 'error' );
  else file_put_contents( 'error.log', print_r( $errorDetails, true ) . PHP_EOL, FILE_APPEND | LOCK_EX );

  if ( $isAjax and ! headers_sent() ) { header( 'Content-Type: application/json' );
    echo json_encode( [
      'status' => 'error', 
      'message' => $debug > 1 ? $msg : 'Oops, something went wrong!', 
      'error' => $errorDetails
    ] );
  } else {
    if ( ! $debug ) http_response_code( 500 );
    echo '<div class="error"><h3>Oops, something went wrong!</h3>', PHP_EOL;
    if ( $debug > 1 ) echo '<hr><pre>', print_r( $errorDetails, true ), '</pre></div>', PHP_EOL;
  }

} ); // set_exception_handler


set_error_handler( function ( $severity, $message, $file, $line ) {

  global $app;

  if ( ! ( $severity & error_reporting() ) ) return;
  $severityNames = [ E_ERROR => 'Fatal', E_NOTICE => 'Notice', E_WARNING => 'Warning', E_PARSE => 'Syntax', 
  E_STRICT => 'Deprecated', E_DEPRECATED => 'Deprecated', E_RECOVERABLE_ERROR => 'Recoverable Error' ];
  $severityName = $severityNames[ $severity ] ?? 'Unknown';
  throw new ErrorException( "ERROR[{$severityName}]: {$message}", E_USER_ERROR, $severity, $file, $line );

} ); // set_error_handler



class ValidationException extends Exception {

  protected $errors;

  public function __construct( $errors )
  {
    parent::__construct( 'Validation error.' );
    $this->errors = $errors;
  }

  public function getErrors()
  {
  	return $this->errors;
  }

}