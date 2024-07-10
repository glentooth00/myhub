<?php /* Admin Module - Clients SPA - Client Add/Edit Sub Controller */

use App\Services\AppForm;

use App\Models\User as UserModel;
use App\Models\Client as ClientModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\ClientS2Mapper as ClientS2MapperModel;
use App\Models\ClientS2Response as ClientS2ResponseModel;
use App\Models\Referrer as ReferrerModel;

use App\Exceptions\ValidationException;



// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? 'new';

if ( ! is_numeric( $id ) and $id !== 'new' )
  respond_with( 'Bad request', 400 );

$isNew = ( $id === 'new' or $id < 0 );
$isEdit = ! $isNew;




// ----------
// -- POST --
// ----------

if ( $app->request->isPost ) {

  $selectFieldNames = [
    'bank'            => 'banks',
    'city'            => 'loc_cities',
    'suburb'          => 'loc_suburbs',
    'province'        => 'loc_provinces',
    'country'         => 'loc_countries',
    'fx_intermediary' => 'ch_intermediaries',
    'referrer_id'     => 'ch_referrers',
    'ncr'             => 'ch_ncrs'
  ];

  debug_log( $_POST, 'IS POST Request - POST: ', 2 );
  debug_log( $_FILES, 'IS POST Request - FILES: ', 2 );
  debug_log( $app->user, 'IS POST Request - User: ', 2 );

  if ( ! $app->request->isAjax ) respond_with( 'Bad request', 400 );

  try {

    $now = date( 'Y-m-d H:i:s' );

    $action = $_POST['action'] ?? '';
    debug_log( $action, 'IS POST Request - Action: ', 3 );


    /** ACTION 1.1 **/

    if ( $action == 'addOption' ) {

      $fieldName = $_POST['fieldName'] ?? '';
      $table = $selectFieldNames[ $fieldName ] ?? null;

      if ( ! $table ) throw new Exception( 'Invalid select field: ' . $fieldName );

      $optName = $_POST['optName'] ?? '';
      if ( ! $optName ) throw new Exception( 'Option name undefined or invalid.' );

      // Check if the new option name exists
      $option = $app->db->table( $table )->where( 'name', $optName )->getFirst();

      if ( $option ) {
        if ( $option->deleted_at ) {
          // Undelete the option
          $referrerModel = new ReferrerModel( $app );
          $updateResult = $referrerModel->unSoftDelete( $option->id );
          debug_log( $updateResult, 'Un-Soft Delete Option Result: ', 2 );

          // Update and exit
          json_response( [ 'success' => true,
            'message' => "Option [$optName] was undeleted.",
            'optName' => $optName,
            'optID' => $option->id
          ] );
        } else {
          throw new Exception( "Another option [$optName] already exists." );
        }
      }

      // Add the new option
      $newOption = [ 'name' => $optName ];

      if ( $table == 'ch_referrers' ) {
        // Set new referrer_id to 8 char UID
        $newOption['referrer_id'] = $option->referrer_id ?? uniqid();
        if ( isset( $_POST['extraData'] ) ) {
          $extraData = json_decode( $_POST['extraData'], true );
          if ( isset( $extraData['id_number'] ) ) $newOption['id_number'] = $extraData['id_number'];
          if ( isset( $extraData['email'] ) ) $newOption['email'] = $extraData['email'];
          if ( isset( $extraData['phone_number'] ) ) $newOption['phone_number'] = $extraData['phone_number'];
        }
      }

      $insertResult = $app->db->table( $table )->insert( $newOption,
        [ 'autoStamp' => true, 'user' => $app->user->user_id ] );
      debug_log( $insertResult, 'Add Option Result: ', 2 );

      json_response( [ 'success' => true,
        'message' => "[$optName] was added successfully.",
        'optName' => $optName,
        'optID' => $insertResult['id']
      ] );

    } // addOption



    /** ACTION 1.2 **/

    if ( $action == 'updateOption' ) {

      $optID = $_POST['optID'] ?? '';
      $optName = $_POST['optName'] ?? ''; 
      $optValue = $_POST['optValue'] ?? ''; // Ref used in client records
      $fieldName = $_POST['fieldName'] ?? '';
      $newOptName = $_POST['newOptName'] ?? '';
      $simpleOptions = $_POST['simpleOptions'] ?? false;

      if ( ! $optValue or ! $optName or ! $newOptName )
        throw new Exception( 'Option data undefined or invalid.' );

      if ( $optName === $newOptName )
        throw new Exception( 'Option value unchanged.' );

      if ( $optID == 'undefined' ) $optID = '';

      $table = $selectFieldNames[ $fieldName ] ?? null;
      if ( ! $table ) throw new Exception( 'Invalid select field: ' . $fieldName );

      $existingOption = $app->db->table( $table )->where( 'name', $newOptName )->getFirst();
      if ( $existingOption ) throw new Exception( "Another option [$newOptName] already exists." );

      // We did not find an existing option with the new value.
      // Good, let's find the target option to by id == optID or name == optValue.
      $option = $optID
        ? $app->db->getFirst( $table, $optID )
        : $app->db->table( $table )->where( 'name', $optValue )->getFirst();

      if ( ! $option ) throw new Exception( "Option [$optName] not found." );

      if ( $simpleOptions ) {
        // Check if the existing option value is already assigned to other clients.
        $inUse = $app->db->table( 'clients' )->where( $fieldName, $option->name )->limit( 2 )->getAll();
        if ( $inUse ) {
          if ( count( $inUse ) > 1 ) {
            // Get the client who's not the current client.
            $otherClient = $inUse[0]->id == $id ? $inUse[1] : $inUse[0];
          } else {
            $otherClient = $inUse[0]->id != $id ? $inUse[0] : null;
          }
          if ( $otherClient )
            throw new Exception( '<span class="nowrap">Can\'t update: <b>' .
              $option->name . '</b></span><br>It is already being used elsewhere.<br>' .
              '<span class="nowrap">See client: <b>' . $otherClient->name .
              " ($otherClient->client_id)</b></span>" );
        }
      }

      $option->name = $newOptName;
      $updateResult = $app->db->table( $table )->update( (array) $option,
        [ 'autoStamp' => true, 'user' => $app->user->user_id ] );
      debug_log( $updateResult, 'Update Option Result: ', 2 );

      json_response( [ 'success' => true,
        'message' => "Option updated from [$optName] to [$newOptName].",
        'optName' => $newOptName
      ] );

    } // updateOption



    /** ACTION 1.3 **/

    if ( $action == 'deleteOption' ) {

      $optID = $_POST['optID'] ?? '';
      $optName = $_POST['optName'] ?? '';
      $optValue = $_POST['optValue'] ?? ''; // Ref used in client records
      $fieldName = $_POST['fieldName'] ?? '';   

      $table = $selectFieldNames[ $fieldName ] ?? null;
      if ( ! $table ) throw new Exception( 'Invalid select field: ' . $fieldName );

      $option = $optID
        ? $app->db->getFirst( $table, $optID )
        : $app->db->table( $table )->where( 'name', $optValue )->getFirst();

      if ( ! $option ) throw new Exception( "Option [$optName] not found." );

      // Check if the option is in use
      $inUse = $app->db->table( 'clients' )->where( $fieldName, $optValue )->getFirst();
      if ( $inUse and $inUse->id != $id )
        throw new Exception( "Can't delete option [$optName]. " .
          "It is already in use. See client [$inUse->client_id]" );

      // check if the table has a "deleted_at" column
      $tableColumns = $app->db->table( $table )->getColumnNames();
      $hasDeletedAt = in_array( 'deleted_at', $tableColumns );

      if ( $hasDeletedAt ) {
        // Soft delete the option
        $referrerModel = new ReferrerModel( $app );
        $updateResult = $referrerModel->softDelete( $option->id );
        debug_log( $updateResult, 'Soft Delete Option Result: ', 2 );
      } else {
        // Delete the option
        $deleteResult = $app->db->table( $table )->delete( $option->id );
        debug_log( $deleteResult, 'Delete Option Result: ', 2 );
      }

      json_response( [ 'success' => true, 
        'message' => "Option [$optName] deleted."
      ] );

    } // deleteOption



    /** ACTION 2 **/

    if ( $action == 'saveClient' ) {

      // ---------
      // Validate
      // ---------

      $clientUid = $_POST['client_id'] ?? '';
      if ( ! $clientUid ) throw new Exception( 'Bad request.' );

      function required( $value, $condition ) {
        return ( $condition and ! $value );
      }

      // Note we have to have the "fieldname" in the message key to allow the UI to highlight the field.
      function invalidMessage( $key, $args = [] ) {
        $messages = [
          'status.required'  => 'Status is required.',
          'id_number.required'  => 'ID Number is required.',
          'id_number.exists' => 'Client with ID Number = %s already exists!',
          'client_id.exists' => 'Client with UID = %s already exists!'
        ];
        $message = $messages[ $key ] ?? 'Unknown Validation Error: ' . $key;
        if ( $args ) $message = vsprintf( $message, is_array( $args ) ? $args : [$args] );
        $keyBase = explode( '.', $key )[0];
        return new ValidationException( [ $keyBase => $message ] );
      }

      $status = $_POST['status'] ?? '';
      $idNumber = $_POST['id_number'] ?? '';
      $marriageCertFile = $_FILES['spare_1_file'] ?? [];
      $marriageCert = $_POST['spare_1'] ?? ( $marriageCertFile['name'] ?? null );
      $cryptoDeclFile = $_FILES['spare_2_file'] ?? [];
      $cryptoDecl = $_POST['spare_2'] ?? ( $cryptoDeclFile['name'] ?? null );

      // Do required validations... required( FieldValue, Only require when this is truthy )
      if ( required( $status  , 'always' ) ) throw invalidMessage('status.required');
      if ( required( $idNumber, 'always' ) ) throw invalidMessage('id_number.required');

      $clientBefore = null;
      $clientModel = new ClientModel( $app );

      // Check if a client exists with the same national ID
      $clientBefore = $app->db->table( 'clients' )->where( 'id_number', $idNumber )->getFirst(); 
      if ( $clientBefore and $clientBefore->id != $id )
        throw invalidMessage( 'id_number.exists', $idNumber );

      if ( ! $clientBefore ) {
        // Check if a client exists with the same UID
        $clientBefore = $app->db->table( 'clients' )->where( 'client_id', $clientUid )->getFirst(); 
        if ( $clientBefore and $clientBefore->id != $id )
          throw invalidMessage( 'client_id.exists', $clientUid );
      }

      if ( ! $clientBefore ) {
        $clientBefore = $clientModel->getClientById( $id );
        // We end up here when creating a new client, or we changed the client UID,
        // or the request id is not a valid client id. i.e. Client not found...
        if ( ! $clientBefore ) throw new Error( "Client id=$id not found." );
      }

      debug_log( $clientBefore, 'clientBefore = ', 2 );

      $referrerId = $_POST['referrer_id'] ?? null;

      // if ( $referrerId and $clientBefore->referrer_id != $referrerId ) {
      //   $referrer = $app->db->getFirst( 'ch_referrers', $referrerId );
      //   if ( ! $referrer ) throw new Error( "Referrer id=$referrerId not found." );

      //   if ( isset( $referrer->user_id ) ) {
      //     $_POST[ 'inhouse_referrer_15_percent' ] = $referrer->user_id;
      //   }

      //   if ( ! $clientBefore->third_party_referrer ) {
      //     $_POST[ 'third_party_referrer' ] = $referrer->name;
      //   }
      // }

      if ( $referrerId ) {
        $referrer = $app->db->getFirst( 'ch_referrers', $referrerId );
        if ( ! $referrer ) throw new Error( "Referrer id=$referrerId not found." );
        $_POST[ 'inhouse_referrer_15_percent' ] = $referrer->user_id;
        $_POST[ 'third_party_referrer' ] = $referrer->name;
      }

      
      // -----------
      // Save Client
      // -----------

      $app->db->pdo->beginTransaction();

      $_POST['sync_at']   = $now;
      $_POST['sync_by']   = $app->user->user_id;
      $_POST['sync_from'] = 'local';
      $_POST['sync_type'] = $isEdit ? 'update' : 'new';

      $ext = $marriageCert ? strtolower( pathinfo( $marriageCert, PATHINFO_EXTENSION ) ) : '';
      $_POST['spare_1'] = $ext ? "marriage_cert.$ext" : null;

      $ext = $cryptoDecl ? strtolower( pathinfo( $cryptoDecl, PATHINFO_EXTENSION ) ) : '';
      $_POST['spare_2'] = $ext ? "crypto_declaration.$ext" : null;

      // TODO: [ 'autoStamp' => $uid ]
      $uid = $app->user->user_id;
      $saveOptions = [ 'autoStamp' => true, 'user' => $uid ];

      $saveResult = $app->db->table( 'clients' )->save( $_POST, $saveOptions );
      if ( $isNew and ! $saveResult ) throw new Exception( 'Failed to save new client.' );
      debug_log( $saveResult, 'Save client result: ', 2 );

      $savedClientId = $saveResult ? $saveResult[ 'id' ] : null;

      if ( ! $savedClientId )
        throw new Exception( 'Failed to save client. No ID returned after save.' );



      // -----------------
      // Update Remote: S2
      // -----------------

      $userEmail = 'neels@currencyhub.co.za';

      // Get the latest version of the client, as saved in the DB
      $client = $app->db->getFirst( 'clients', $savedClientId );
      debug_log( $client, 'Saved client = ', 2 );

      if ( ! $client ) throw new Exception( 'Client not found.' );

      $clientHasRelevantChanges = (
        $clientBefore->sda_mandate != $client->sda_mandate ||
        $clientBefore->fia_mandate != $client->fia_mandate
      );

      // Choose remote update extent...
      if ( $isNew or $clientHasRelevantChanges ) {

        // First, update and calculate everything affected by this update locally
        $clientStateModel = new ClientStateModel( $app );
        $updateResult = $clientStateModel->updateStateFor( $client );
        debug_log( $updateResult, 'Client Update SDA/FIA Allowances Result: ', 3 );

        // Then, sync all the local changes with S2 (Google Sheets)
        $responseModel = new ClientS2ResponseModel( $app );
        $payload = $responseModel->generate( $updateResult );

        run_google_script( 'submitClient', $payload, $userEmail );

        // What about statement URL Links? See below...

      } else {

        $clientS2Mapper = new ClientS2MapperModel();

        // Only Upsert client data, don't update calculated values or statements.
        // PS: Client side should check and prevent save if no changes were made.
        $payload = [ 'sheetName' => __GOOGLE_CLIENTS_SHEET_NAME__, 'primaryKey' => 'Client ID',
           'row' => $clientS2Mapper->mapClientToRemoteRow( $client ) ];

        run_google_script( 'upsertRow', $payload, $userEmail );     

        // What about statement URL Links?

        // How / when do we tell S2 to generate / update a statement?
        // Can we get the link info in the "upsertRow" API call response?

        // We should not be creating PDFs anymore, so only the "Google Sheet version" of the
        // statement needs to be generated and the link saved in the DB, until we no longer need
        // it at all! We might start saving links to annual/quarterly PDF versions sent to
        // the client however?

        // Should we explicitly make an extra API call to generate a new statement and get the statement URL link?

      }


      // ---------------
      // Process Uploads
      // ---------------

      // This section needs to be AFTER saving! We need a new client's ID to get a save dir.
      $saveDir = $app->uploadsDir . __DS__ . $client->name . '_' . $client->id . __DS__ . 'docs';

      $clientData = [ 'id' => $savedClientId ];

      $clientData['spare_1'] = process_upload( 'spare_1_file', 
        $clientBefore->spare_1 ?? '', $marriageCert, $saveDir, 'spare_1' );

      $clientData['spare_2'] = process_upload( 'spare_2_file', 
        $clientBefore->spare_2 ?? '', $cryptoDecl, $saveDir, 'spare_2' );

      // Update the client's upload field values after processing.
      $updateResult = $app->db->table( 'clients' )->update( $clientData );
      debug_log( $updateResult, 'Update client UPLOADS result: ', 2 );


      $app->db->pdo->commit();


      json_response( [ 'success' => true, 'id' => $savedClientId, 'goto' => 'back' ] );

    } // saveClient



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

} // POST




// ---------
// -- GET --
// ---------

function fullName( $user )
{
  return $user->first_name . ( $user->last_name ? ' ' . $user->last_name : '' );
}


function nameAndEmail( $user )
{
  return $user->name . ' <small>&lt;' . ( ( $user->email && $user->email != 'NULL' )
    ? $user->email : 'no@email' ) . '&gt;</small>';
}


$form = new AppForm();

$clientModel = new ClientModel( $app );
$client = $clientModel->getClientById( $id );
if ( ! $client ) respond_with( "Client id=$id not found", 404 );
debug_log( $client, 'Editing client: ', 3 );


// for clients dropdown
$accountant = 'All';
$clientModel = new ClientModel( $app );
$clients = $clientModel->getAllByAccountant( $accountant, [ 'except' => $id ] );

$banks = $app->db->table( 'banks' )->orderBy( 'name' )->getAll();
$cities = $app->db->table( 'loc_cities' )->orderBy( 'name' )->getAll();
$suburbs = $app->db->table( 'loc_suburbs' )->orderBy( 'name' )->getAll();
$provinces = $app->db->table( 'loc_provinces' )->orderBy( 'name' )->getAll();
$countries = $app->db->table( 'loc_countries' )->orderBy( 'name' )->getAll();

$userModel = new UserModel( $app );
$accountants = $userModel->getUsersByRole( 'accountant' );

$personalAccountant = new stdClass();
$personalAccountant->first_name = 'Personal';
$personalAccountant->last_name = null;

$accountants = array_merge( [ $personalAccountant ], $accountants );

// $internalUsers = $userModel->getInternalUsers();

$referrers = $app->db->table( 'ch_referrers' )
  ->where( 'deleted_at', 'IS', null )
  ->getAll();

$ncrs = $app->db->table( 'ch_ncrs' )
  ->orderBy( 'name' )
  ->getAll();

// $intermediaries = $app->db->table( 'ch_intermediaries' )->orderBy( 'name' )->getAll();
// $traders = $userModel->getUsersByRole( 'trader' );

$super = in_array( $app->user->role, ['super-admin', 'sysadmin'] );