<?php /* API Endpoint Controller: "api/v1/tccs/expire" */

require __DIR__ . '/../../api.php';


// ---------
// -- CLI --
// ---------

if ( $app->request->cli ) {

  debug_log( 'API endpoint: "api/v1/tccs/expire" says hi!  ' .
    'Request type = CLI ' . $_SERVER['REQUEST_METHOD'], '', 2 );

  // Check if there are arguments passed (besides the script name)
  if ($argc > 1) { for ($i = 1; $i < $argc; $i++) { debug_log( "Argument $i: $argv[$i]" ); } } 
  else { debug_log( 'No arguments passed to the script. Exit' ); exit; }

  use_database();
  debug_log( 'Database connected.', '', 3 );

  try {

    $command = $argv[1]; // $argv[0] is the script name

    debug_log( $command, 'command = ' );

    switch ( $command )
    {
      case 'expireTccs':

        debug_log( 'OK, so we are expiring tccs!' );

        $app->db->pdo->beginTransaction();

        $sql = 'UPDATE `tccs`
          SET `expired` = CASE
                            WHEN `rollover` > 0 THEN YEAR(`date`) + 1
                            ELSE YEAR(`date`)
                          END,
              `status` = "Expired"
          WHERE `status` = "Approved" 
            AND `date` IS NOT NULL 
            AND `date` < DATE_SUB(NOW(), INTERVAL 1 YEAR)';

        $rowCount = $app->db->execute( $sql );

        debug_log( $rowCount, 'Rows updated (expired) = ' );

        $app->db->pdo->commit();

        echo date( 'Y-m-d H:i:s' ), ' - Expire TCCs: ', $rowCount, ' rows expired.';

        break;

      default: 
        throw new Exception( 'Invalid or missing CLI command.' );
    }

  }

  catch ( Exception $ex ) {
    $app->logger->log( $ex->getMessage(), 'error' );
    $app->db->safeRollback();
  }

  exit; // Just in case we forget to exit after a response.

}



// ----------------
// -- POST + GET --
// ----------------

respond_with( 'Bad request', 400 );