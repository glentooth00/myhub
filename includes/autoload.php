<?php /* autoload.php */

if ( ! is_dir( __VENDORS_DIR__ . __DS__ . 'F1' ) )
  throw new Exception( 'F1 lib is required. Please clone it from GitHub: ' .
    'https://github.com/xaviertnc/OneFile.git into vendors/F1' );


spl_autoload_register(function ($className) {

  global $app;

  $nameParts = explode( '\\', $className );
  $libName = $nameParts[0] ?? 'Vendors';
  $fileName = array_pop( $nameParts ) . '.php';

  // $app->bootLog[] = "Autoload: $className, Lib: $libName, " .
    // "File: $fileName, Path: " . json_encode( $nameParts );
  
  $baseDir = $libName === 'App' ? __INCLUDES_DIR__ : __VENDORS_DIR__;
  $path = $libName === 'App' 
    ? strtolower( implode( __DS__, array_slice( $nameParts, 1 ) ) )
    : implode( __DS__, $nameParts );

  $file = $baseDir . __DS__ . $path . __DS__ . $fileName;

  // $app->bootLog[] = "Autoload: $file";

  if ( ! file_exists( $file ) )
    throw new Exception( 'Class **file** not found: ' . $file );

  include_once $file;

});