<?php namespace App\Services;

/**
 * App Request Class - 11 Oct 2023
 * 
 * Apache Web Server Edition
 * 
 * Reference $_SERVER, $_GET and $_POST to provide
 * some useful request properties and methods 
 * with a consistent API.
 * 
 * @author Neels Moller <xavier.tnc@gmail.com>
 * 
 * @version 2.1 - DEV - 05 Jun 2024
 *   - Remove 'run.php' option from getControllerFile()
 *   - Add check for last segment as filename in getControllerFile()
 *
 */

class AppRequest {

  public $uri;
  public $cli;
  public $type;
  public $path;
  public $query;
  public $module;
  public $isAjax;
  public $isPost;
  public $method;
  public $referer;
  public $lastSegment;
  public $pathSegments;
  public $controllerDir;
  public $controllerFile;
  public $controllerFilePath;
  public $controllerFileName;
  public $errorsDir;
  public $itemId;
  

  public function __construct( $app, $options = [] ) {

    $this->cli = ( php_sapi_name() == 'cli' );

    if ( $this->cli )
    {
      $_GET = $options[ 'get' ] ?? [];
      $_POST = $options[ 'post' ] ?? [];
      $_SERVER[ 'REQUEST_URI' ] = $options[ 'uri' ] ?? '';
      $_SERVER['HTTP_REFERER'] = $options[ 'referer' ] ?? '';
      $_SERVER['REQUEST_METHOD'] = $options[ 'method' ] ?? 'GET';
      $_SERVER['HTTP_X_REQUESTED_WITH'] = $options[ 'isAjax' ] ?? null;
      $_SERVER['REMOTE_ADDR'] = $options[ 'ip' ] ?? 'localhost';
      $_SERVER['HTTP_HOST'] = $options[ 'host' ] ?? 'localhost';
      $_SERVER['HTTP_USER_AGENT'] = $options[ 'agent' ] ?? 'CLI';
    }

    $uri = $_SERVER[ 'REQUEST_URI' ];

    $parts = explode( '?', $uri, 2 );
    if ( count( $parts ) == 2 ) {
      $this->uri = $parts[0]; 
      $this->query = $parts[1];
    }
    else {
      $this->uri = $uri;
      $this->query = '';
    }

    $home = 'user/login';
    $path = trim( $this->uri, '/' );

    if ( __BASE_REF__ ) {
      if ( $path == __BASE_REF__ ) $path = $home;
      else $path = replace_start( __BASE_REF__ . '/', '', $path );
    }

    $segments = explode( '/', $path ?: $home );
    
    // Set the requested module if the first request segment is a known module,
    // or assume we want the default module, and add it to the segments array.
    if ( in_array( $segments[0], $app->modules ) ) $this->module = $segments[0];
    else array_unshift( $segments, 'website' );
    
    // Remove the last segment from the segments array if it is a numeric value.
    $this->itemId = ( count( $segments ) > 1 && is_numeric( end( $segments ) ) )
      ? intval( array_pop( $segments ) ) : null;

    $this->path = $path;
    $this->pathSegments = $segments;
    $this->lastSegment = end( $segments );

    $this->controllerFileName = $this->lastSegment . '.php';
    $this->controllerFilePath = implode( __DS__, $segments );
    $this->controllerDir = $app->pagesDir . __DS__ . $this->controllerFilePath;
    $this->controllerFile = $this->controllerDir . __DS__ . $this->controllerFileName;
    $this->errorsDir = $app->pagesDir . __DS__ . 'user' . __DS__ . 'error';

    $this->referer = $_SERVER[ 'HTTP_REFERER' ] ?? '';
    $this->method = $_SERVER[ 'REQUEST_METHOD' ] ?? 'GET';
    $this->isAjax = $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ?? false;
    $this->type = $_SERVER[ 'HTTP_F1_REQUEST_TYPE' ] ?? null;
    $this->isPost = $this->method == 'POST';
  }


  public function getControllerFile() {
    if ( file_exists( $this->controllerFile ) ) return $this->controllerFile;
    $this->controllerFile = $this->controllerDir . '.php'; // Last segment is actually a filename?
    if ( file_exists( $this->controllerFile ) ) return $this->controllerFile;
    return $this->errorsDir . __DS__ . '404.php';
  }

}