<?php /* Accountant Module - TCCs SPA - Add/Edit TCC Sub Controller */

use App\Services\AppForm;

use App\Models\Tcc as TccModel;
use App\Models\Client as ClientModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\ClientS2Response as ClientS2ResponseModel;

use App\Exceptions\ValidationException;


// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? 'new';

if ( ! is_numeric( $id ) and $id !== 'new' )
  respond_with( 'Bad request', 400 );

$isNew = ( $id === 'new' );
$isEdit = ! $isNew;



// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $_FILES, 'IS POST Request - FILES: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $now = date( 'Y-m-d H:i:s' );

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 2 );


    /** ACTION 1 **/

    if ( $action == 'saveTcc' ) {

      // ---------
      // Validate
      // ---------

      function required( $value, $condition ) {
        return ( $condition and ! $value );
      }

      function invalidMessage( $key, $args = [], $related = null ) {
        $messages = [
          'status.required'           => 'Status is required.',
          'application_date.required' => 'Applied On is required.',
          'amount_cleared.required'   => 'Amount is required.',
          'client_id.notfound'        => 'Client not found.',
          'client_id.required'        => 'Please select a client.',
          'tax_case_no.required'      => 'Tax Case No is required to upload a Tax Certificate PDF.',
          'tcc_pin.required'          => 'PIN Number is required to upload a Tax Certificate PDF.',
          'tcc_pin.exists'            => 'This PIN Number is already used for TCC ID = %s',
          'date.required'             => 'Approved On date is required to upload a Tax Certificate PDF.',
          'tax_cert_pdf.required'     => 'Please upload a Tax Certificate PDF.',
          'tax_cert_pdf.related'      => 'Please upload a Tax Certificate PDF before setting %s.',
        ];
        $message = $messages[ $key ] ?? 'Unknown Validation Error: ' . $key;
        if ( $args ) $message = vsprintf( $message, is_array( $args ) ? $args : [$args] );
        $keyBase = explode( '.', $key )[0];
        return new ValidationException( [ $keyBase => $message ] );
      }

      $date = $_POST['date'] ?? '';
      $status = $_POST['status'] ?? '';
      $tccPin = $_POST['tcc_pin'] ?? '';
      $clientUid = $_POST['client_id'] ?? '';
      $taxCaseNo = $_POST['tax_case_no'] ?? '';
      $applyDate = $_POST['application_date'] ?? '';
      $taxCertPdfFile = $_FILES['tax_cert_pdf_file'] ?? [];
      $tccAmount = decimal( $_POST['amount_cleared'] ?? '', 0 );
      $reserved = decimal( $_POST['amount_reserved'] ?? '', 0 );
      $rollover = decimal( $_POST['rollover'] ?? '', 0 );
      $used = decimal( $_POST['amount_used'] ?? '', 0 );
      $taxCertPdf = $_POST['tax_cert_pdf'] ?? ( $taxCertPdfFile['name'] ?? null );
      $issued = $status == 'Approved' or $tccPin or $taxCertPdf;

      // Do required validations... required( FieldValue, Only require when this is truthy )
      if ( required( $status    , 'always'    ) ) throw invalidMessage('status.required');
      if ( required( $clientUid , 'always'    ) ) throw invalidMessage('client_id.required');
      if ( required( $tccAmount , 'always'    ) ) throw invalidMessage('amount_cleared.required');
      if ( required( $applyDate , 'always'    ) ) throw invalidMessage('application_date.required');
      if ( required( $taxCaseNo , $taxCertPdf ) ) throw invalidMessage('tax_case_no.required');
      if ( required( $taxCertPdf, $issued     ) ) throw invalidMessage('tax_cert_pdf.related', 'an issued related state.');
      if ( required( $taxCertPdf, $rollover   ) ) throw invalidMessage('tax_cert_pdf.related', 'Rollover');
      if ( required( $taxCertPdf, $used       ) ) throw invalidMessage('tax_cert_pdf.related', 'Amount Used');
      if ( required( $tccPin    , $taxCertPdf ) ) throw invalidMessage('tcc_pin.required');
      if ( required( $date      , $taxCertPdf ) ) throw invalidMessage('date.required');

      // Ensure we have a valid client
      $client = $app->db->table( 'clients' )->where( 'client_id', $clientUid )->getFirst(); 
      if ( ! $client ) throw invalidMessage('client_id.notfound');

      // Get current TCC if it exists + Check if the TCC PIN is not already used
      $tccBefore = null;
      $tccModel = new TccModel( $app );
 
      if ( $tccPin ) {
        $tccBefore = $app->db->table( 'tccs' )->where( 'tcc_pin', $tccPin )->getFirst();
        if ( $tccBefore and $tccBefore->id != $id ) throw invalidMessage( 'tcc_pin.exists', $tccBefore->id );
      }

      if ( ! $tccBefore ) {
        $tccBefore = $tccModel->getTccById( $id ); // Returns a blank, but valid, TCC if $id = 'new' or <= 0
        // $tccBefore will only be null if $id > 0, but the TCC doesn't exist
        if ( ! $tccBefore ) throw new Error( "TCC id=$id not found." );
      }

      debug_log( $tccBefore, 'tccBefore = ', 3 );


      
      // ---------
      // Save TCC
      // ---------

      $app->db->pdo->beginTransaction();

      debug_log( $tccAmount, 'amount_cleared: ', 2 );
      debug_log( $reserved, 'amount_reserved: ', 2 );
      debug_log( $rollover, 'rollover: ', 2 );
      debug_log( $used, 'amount_used: ', 2 );

      $clearedNet = $rollover ?: $tccAmount - $reserved;
      debug_log( $clearedNet, 'amount cleared net: ', 2 );
      
      $remaining = $clearedNet - $used;
      debug_log( $remaining, 'amount remaining: ', 2 );

      $_POST['amount_cleared']     = $tccAmount;
      $_POST['amount_remaining']   = $remaining;
      $_POST['amount_available']   = ( $status == 'Approved' ) ? $remaining : 0;
      $_POST['amount_cleared_net'] = $clearedNet;

      $_POST['sync_at'] = $now;
      $_POST['sync_by'] = $app->user->user_id;
      $_POST['sync_from'] = 'local';
      $_POST['sync_type'] = $isEdit ? 'update' : 'new';

      $_POST['tax_cert_pdf'] = trim( $tccPin ) . '.pdf';

      $saveOptions = [ 'autoStamp' => true, 'user' => $app->user->user_id ];
      $saveResult = $app->db->table( 'tccs' )->save( $_POST, $saveOptions );
      if ( $isNew and ! $saveResult ) throw new Exception( 'Failed to save new TCC.' );
      debug_log( $saveResult, 'Save TCC apiResult: ', 2 );

      $savedTccId = $saveResult ? $saveResult[ 'id' ] : null;

      if ( ! $savedTccId )
        throw new Exception( 'Failed to save TCC. No ID returned after save.' );


      // -----------------
      // Update Remote: S2
      // -----------------

      $userEmail = 'neels@currencyhub.co.za';

      // Get the latest version of the TCC, as saved in the DB
      $tcc = $app->db->getFirst( 'tccs', $savedTccId );
      debug_log( $tcc, 'Saved TCC = ', 2 );

      if ( ! $tcc ) throw new Exception( 'TCC not found.' );

      $tccHasRelevantChanges = (
        $tccBefore->amount_cleared != $tcc->amount_cleared ||
        $tccBefore->amount_reserved != $tcc->amount_reserved ||
        $tccBefore->date != $tcc->date ||
        $tccBefore->status != $tcc->status ||
        $tccBefore->expired != $tcc->expired
      );

      // Choose remote update extent...
      if ( $isNew or $tccHasRelevantChanges ) {

        // First, update and calculate everything affected by this TCC locally
        $clientStateModel = new ClientStateModel( $app );
        $updateResult = $clientStateModel->updateStateFor( $client );
        debug_log( $updateResult, 'Client Update State Result: ', 3 );

        // Then, sync all the local changes with S2 (Google Sheets)
        // We exclude statement data to prevent generating a new statement.
        // TCC's don't affect a client's statement lines.
        $responseModel = new ClientS2ResponseModel( $app, [ 'no-statement' => true ] );
        $responseModel->add( 'tccData', $tccModel->mapTccToRemoteRow( $tcc ) );
        $payload = $responseModel->generate( $updateResult );

        run_google_script( 'submitTcc', $payload, $userEmail );

      } else {

        // Only Upsert TCC data, don't update calculated values or statements.
        // PS: Client side should check and prevent save if no changes were made
        $payload = [ 'sheetName' => __GOOGLE_TCCS_SHEET_NAME__, 'primaryKey' => 'TCC ID',
           'row' => $tccModel->mapTccToRemoteRow( $tcc ) ];

        run_google_script( 'upsertRow', $payload, $userEmail );

      }


      // ---------------
      // Process Uploads
      // ---------------

      // This section needs to be AFTER saving!
      // We need a new client's ID to get a save dir.
      $saveDir = $app->uploadsDir . __DS__ . $client->name . '_' . $client->id;

      $tccFiles = [ 'id' => $savedTccId ];

      $certFileBase = trim( $tccPin );
      $tccFiles['tax_cert_pdf'] = process_upload( 'tax_cert_pdf_file', 
        $tccBefore->tax_cert_pdf ?? '', $taxCertPdf, $saveDir, $certFileBase );

      // Update the client's upload field values after processing.
      $updateResult = $app->db->table( 'tccs' )->update( $tccFiles );
      debug_log( $updateResult, 'Update tccs UPLOADS apiResult: ', 2 );


      $app->db->pdo->commit();

      json_response( [ 'success' => true, 'id' => $savedTccId, 'goto' => 'back' ] );

    } // saveTcc



    /** DEFAULT ACTION **/

    json_response( [ 'success' => false, 'message' => 'Invalid request' ] );  

  }

  catch ( ValidationException $ex ) {
    $errors = $ex->getErrors();
    debug_log( $errors, 'Validation Exception: ' );
    $app->db->safeRollBack();
    abort_uploads();
    json_response( [ 'success' => false, 'message' => $ex->getMessage(), 'errors' => $errors ] );
  }

  catch ( Exception $ex ) {
    $error = $ex->getMessage();
    $app->logger->log( $error, 'error' );
    $app->db->safeRollBack();
    abort_uploads();
    json_response( [ 'success' => false, 'message' => $error ] );
  }

}



// ---------
// -- GET --
// ---------

$form = new AppForm();

$tccModel = new TccModel( $app );
$tcc = $tccModel->getTccById( $id );
if ( ! $tcc ) respond_with( "TCC id=$id not found.", 404 );
debug_log( $tcc, 'Editing tcc: ', 2 );

// for status dropdown
$statuses = array( 'Pending', 'Awaiting Docs', 'Approved', 'Declined', 'Expired', 'Used' );

// for clients dropdown
$accountant = full_name($app->user);
$clientModel = new ClientModel( $app );
$clients = $clientModel->getAllByAccountant( $accountant, 'Active' );