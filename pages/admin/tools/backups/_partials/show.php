<?php /* Admin Mudule - Tools Backups SPA - Show Sub Controller */

use App\Services\AppBackup;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  respond_with( 'Bad request', 400 );

}



// ---------
// -- GET --
// ---------

$action = $_GET['do'] ?? 'show';

if ( $action == 'zipAppCode' ) {

  $backupModel = new AppBackup();

  $zipFile = $backupModel->zipAppCode( [ 'rootDir' => __ROOT_DIR__, 'prefix' => $app->ver ] );
  $zipFileName = basename( $zipFile );
  $zipFileRef = $app->baseUri . 'backups/' . $zipFileName;

  include 'backup-success.html';

  exit;

}