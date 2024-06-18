<?php namespace App\Services;

use F1\View;

/**
 * App Logger Class - 25 Nov 2023
 * 
 * @author Neels Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.3 - DEV - 10 Jan 2024
 *  - Make $theme and $variant configurable via $options
 *  - Remove $variant contructor argument
 *  - Add $options constructor argument
 *  - Remove $error404File property
 *  - Add $errorsDir property
 */

class AppView extends View {

  /**
   * F1\View
   * 
   * public $file;
   * public $data;
   * 
   */

  public $dir;
  public $name;
  public $theme;
  public $variant;
  public $errorsDir;
  public $compiledDir;
  public $manifestDir;
  public $templatesDir;


  public function __construct( $app, $options = [] ) {

    $variant = $options['variant'] ?? null;
    $theme = $options['theme'] ?? $app->theme ?? 'default-theme';

    $dir = $app->request->controllerDir;
    
    $module = $app->request->module;

    $compiledDir = $app->storageDir . __DS__ . 'viewcache' . __DS__ . $module;
    if ( ! is_dir( $compiledDir ) ) mkdir( $compiledDir, 0777, true );

    $manifestDir = $app->storageDir . __DS__ . 'manifest' . __DS__ . $module;
    if ( ! is_dir( $manifestDir ) ) mkdir( $manifestDir, 0777, true );

    // A tricky bit of logic here: if the request is AJAX, we want to load the partial view file.
    // The name also needs to be different so that the compiled file is different.
    $name = $app->request->lastSegment . ( $variant ? "-$variant" : '' );

    if ( $app->request->isAjax ) {
      $name .= '-partial';
      if ( ! $variant ) $variant = 'show';
      $file = $dir . __DS__ . $app->partialsRef . __DS__ . "$variant.html";
    }
    else {
      $file = $dir . __DS__ . $name . '.html';
    }

    $this->dir = $dir;
    $this->name = $name;
    $this->theme = $theme;
    $this->variant = $variant;
    $this->compiledDir = $compiledDir;
    $this->manifestDir = $manifestDir;
    $this->templatesDir = $app->templatesDir;
    $this->errorsDir = $app->pagesDir . __DS__ . 'user' . __DS__ . 'error';

    // Sets $this->file and initializes $this->data
    // We don't set data here, we'll use the with() method instead.
    parent::__construct( $file );

  } // __construct


  public function recompileChanges() { return __ENV_PROD__ ? false : true; }


  // This method only gets called if no compiled view file exist AND when not in production mode.
  // We don't care about performance here and just try every possible location for the include file.
  public function getIncludeFile( $inclFileRef, $props = [] ) {
    $filePath = $this->dir . __DS__ . $inclFileRef;
    if ( file_exists( $filePath ) ) return $filePath;
    $filePath = $filePath . '.html'; if ( file_exists( $filePath ) ) return $filePath;
    $filePath = $filePath . '.php'; if ( file_exists( $filePath ) ) return $filePath;

    $filePath = $this->templatesDir . __DS__ . $inclFileRef;
    if ( file_exists( $filePath ) ) return $filePath;
    $filePath = $filePath . '.html'; if ( file_exists( $filePath ) ) return $filePath;
    $filePath = $filePath . '.php'; if ( file_exists( $filePath ) ) return $filePath;
  }


  public function getCompiledFile() { return $this->compiledDir . __DS__ . $this->name . '.php'; }

  public function getManifestFile() { return $this->manifestDir . __DS__ . $this->name . '.php'; }

  public function get404File() { return $this->errorsDir . __DS__ . '404.php'; }

} // AppView