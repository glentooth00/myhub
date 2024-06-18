<?php namespace App\Services;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveCallbackFilterIterator;
use FilesystemIterator;
use ZipArchive;

/**
 * App Backup Class - 23 Jan 2024
 * 
 * @author Neels Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.0 - INIT - 23 Jan 2024
 *
 */

class AppBackup {

  private $excludedPaths;


  public function __construct( $options = [] ) {
    $this->excludedPaths = [ '.git', 'storage', 'uploads', 'tests', '.env-local', '.env-test', 
      'system2.json', 'myhub.sublime-project', 'myhub.sublime-workspace', 'node_modules' ];
    $this->excludedPaths = array_merge( $this->excludedPaths, $options['exclude'] ?? [] );
  }


  private function shouldIncludeFile( $rootDir, $file, $excludedPaths ) {
    $relativePath = substr( str_replace('\\', '/', $file->getPathname() ), strlen( $rootDir ) + 1 );
    foreach ( $excludedPaths as $excludedPath ) {
      if ( strpos( $relativePath, $excludedPath ) === 0 || basename( $relativePath ) === $excludedPath) {
        return false;
      }
    }
    return pathinfo($file->getPathname(), PATHINFO_EXTENSION) !== 'zip';
  }


  private function addFilesToZip( $rootDir, $zip, $excludedPaths ) {
    $directory = new RecursiveDirectoryIterator( $rootDir, FilesystemIterator::SKIP_DOTS );
    $filter = new RecursiveCallbackFilterIterator( $directory, function ($file) use ($rootDir, $excludedPaths) {
      return $this->shouldIncludeFile($rootDir, $file, $excludedPaths);
    });
    $iterator = new RecursiveIteratorIterator( $filter );
    foreach ( $iterator as $file ) {
      $filePath = $file->getRealPath();
      $relativePath = str_replace( '\\', '/', substr( $filePath, strlen( $rootDir ) + 1 ) );
      if ( $zip->addFile( $filePath, $relativePath ) ) {
        $zip->setExternalAttributesName( $relativePath, ZipArchive::OPSYS_UNIX, 
          (0100644 << 16), ZipArchive::OPSYS_UNIX );
      }
    }
  }


  public function zipAppCode( $options = [] ) {
    $rootDir = isset($options['rootDir']) ? realpath($options['rootDir']) : __DIR__; 
    $prefix = $options['prefix'] ?? 'backup';
    $backupFileName = $options['saveAs'] ?? $prefix . '_' . date('Ymd_His') . '.zip';
    $excludedPaths = array_merge( $this->excludedPaths, $options['exclude'] ?? [] );

    $zip = new ZipArchive();
    $realDir = realpath( $rootDir );
    $backupsDir = $realDir . DIRECTORY_SEPARATOR . 'backups';
    if ( ! file_exists( $backupsDir ) ) mkdir( $backupsDir, 0755, true );
    $zipFile = $backupsDir . DIRECTORY_SEPARATOR . $backupFileName;
    if ( $zip->open( $zipFile, ZipArchive::CREATE ) !== TRUE ) {
      throw new Exception( "Cannot open <$zipFile>" );
    }
    $this->addFilesToZip( $realDir, $zip, $excludedPaths );
    $zip->close();
    return $zipFile;
  }

} // AppBackup
