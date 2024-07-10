<?php /* Accountant Module - TCCs SPA - Add/Edit TCC Sub Controller */

global $app;

use App\Services\AppForm;

use App\Models\Tcc as TccModel;
use App\Models\Trade as TradeModel;
use App\Models\TccS2Mapper as TccS2MapperModel;
use App\Models\ClientS2Response as ClientS2ResponseModel;
use App\Models\ClientState as ClientStateModel;
use App\Models\Client as ClientModel;

use App\Exceptions\ValidationException;



function array_find($array, $key, $value)
{
  foreach ($array as $item) {
    if ($item->$key == $value)
      return $item;
  }
  return null;
}


function getAllocsBreakdown($app, $allocs, $validate = false)
{
  $tradeUIDs = [];
  $totalUsed = 0;
  foreach ($allocs as $tradeUID => $coverAmount) {
    if ($tradeUID != '_FIX_')
      $tradeUIDs[] = $tradeUID;
    $totalUsed += floatval($coverAmount);
  }
  $tradeModel = new TradeModel($app);
  $trades = $tradeModel->getTradesByUid($tradeUIDs);
  if ($validate and count($trades) != count($tradeUIDs)) {
    $missingTrades = array_values(array_diff($tradeUIDs, array_column($trades, 'trade_id')));
    throw new Exception('Some trade(s) allocated could not be found. ' . json_encode($missingTrades));
  }
  $tradesByYear = [];
  $totalUsedByYear = [];
  foreach ($trades as $trade) {
    $year = date('Y', strtotime($trade->date));
    $tradesByYear[$year][] = $trade;
    $totalUsedByYear[$year] = ($totalUsedByYear[$year] ?? 0) + floatval($allocs[$trade->trade_id]);
  }
  return compact('totalUsed', 'totalUsedByYear', 'tradesByYear', 'trades');
}



// -------------
// -- REQUEST --
// -------------

$id = $_GET['id'] ?? 'new';

if (!is_numeric($id) and $id !== 'new')
  respond_with('Bad request', 400);

$isNew = ($id === 'new' or $id < 0);
$isEdit = !$isNew;



// ----------
// -- POST --
// ----------

if ($app->request->isPost) {

  debug_log($_POST, 'IS POST Request - POST: ', 2);
  debug_log($_FILES, 'IS POST Request - FILES: ', 2);
  debug_log($app->user, 'IS POST Request - User: ', 2);

  if (!$app->request->isAjax)
    respond_with('Bad request', 400);

  try {

    $now = date('Y-m-d H:i:s');

    $action = $_POST['action'] ?? '';
    debug_log($action, 'IS POST Request - Action: ', 2);


    /** ACTION 1 **/

    if ($action == 'saveTcc') {

      $tccBefore = null;
      $tccModel = new TccModel($app);


      // ---------
      // Validate
      // ---------

      function required($value, $condition)
      {
        return ($condition and !$value);
      }

      function invalidMessage($key, $args = [])
      {
        $messages = [
          'status.required' => 'Status is required.',
          'status.notallowed' => 'Status not allowed. %s',
          'application_date.required' => 'Applied On is required.',
          'amount_cleared.required' => 'Amount is required.',
          'client_id.notfound' => 'Client not found.',
          'client_id.required' => 'Please select a client.',
          'tax_case_no.required' => 'Tax Case No is required to upload a Tax Certificate PDF.',
          'tcc_pin.required' => 'PIN Number is required to upload a Tax Certificate PDF.',
          'tcc_pin.exists' => 'This PIN Number is already used for TCC ID = %s',
          'date.required' => 'Approved On date is required to upload a Tax Certificate PDF.',
          'tax_cert_pdf.required' => 'Please upload a Tax Certificate PDF.',
          'tax_cert_pdf.related' => 'Please upload a Tax Certificate PDF before setting %s.',
          'allocated_trades.over' => 'Allocated amount(s) exceed the cleared amount.',
        ];
        $message = $messages[$key] ?? 'Unknown Validation Error: ' . $key;
        if ($args)
          $message = vsprintf($message, is_array($args) ? $args : [$args]);
        $keyBase = explode('.', $key)[0];
        return new ValidationException([$keyBase => $message]);
      }

      $date = $_POST['date'] ?? '';
      $status = $_POST['status'] ?? '';
      $tccPin = trim($_POST['tcc_pin'] ?? '');
      $clientUid = $_POST['client_id'] ?? '';
      $taxCaseNo = trim($_POST['tax_case_no'] ?? '');
      $applyDate = $_POST['application_date'] ?? '';
      $taxCertPdfFile = $_FILES['tax_cert_pdf_file'] ?? [];
      $taxCertPdf = $_POST['tax_cert_pdf'] ?? ($taxCertPdfFile['name'] ?? null);
      $allocatedTradesJson = $_POST['allocated_trades'] ?? null;
      $issued = ($status == 'Approved' or $tccPin or $taxCertPdf);
      $overrideValidation = isset($_POST['override_validation']);
      $tccAmount = decimal($_POST['amount_cleared'] ?? '', 0);
      $reserved = decimal($_POST['amount_reserved'] ?? '', 0);
      $rollover = decimal($_POST['rollover'] ?? '', 0);
      $remaining = decimal($_POST['amount_remaining'] ?? '', 0);
      $available = decimal($_POST['amount_available'] ?? '', 0);
      $used = decimal($_POST['amount_used'] ?? '', 0);
      $forceUsed = isset($_POST['force_used']);
      $expiredIn = $_POST['expired'] ?? null;


      if ($date) {
        $tccIssuedAt = strtotime($date);
        $tccYear = date('Y', $tccIssuedAt);
        $expiresAt = strtotime('+1 year', $tccIssuedAt);
        $isExpired = $expiresAt < time();
      } else {
        $tccYear = date('Y');
        $isExpired = false;
      }


      if (!$overrideValidation) {

        if ($allocatedTradesJson and $status != 'Approved' and $status != 'Expired') {
          throw invalidMessage('status.notallowed', 'TCC has allocated trades.');
        }

        if ($taxCertPdf and ($status == 'Pending' or $status == 'Awaiting Docs')) {
          throw invalidMessage('status.notallowed', 'TCC already has a Tax Cert PDF.');
        }

        if ($isExpired and $status == 'Approved') {
          throw invalidMessage('status.notallowed', 'TCC is expired.');
        }

        // Do required validations... required( FieldValue, Only require when this is truthy )
        if (required($status, 'always'))
          throw invalidMessage('status.required');
        if (required($clientUid, 'always'))
          throw invalidMessage('client_id.required');
        if (required($tccAmount, 'always'))
          throw invalidMessage('amount_cleared.required');
        if (required($applyDate, 'always'))
          throw invalidMessage('application_date.required');
        if (required($taxCaseNo, $taxCertPdf))
          throw invalidMessage('tax_case_no.required');
        if (required($taxCertPdf, $issued))
          throw invalidMessage('tax_cert_pdf.related', 'an "issued" related state.');
        if (required($taxCertPdf, $rollover))
          throw invalidMessage('tax_cert_pdf.related', 'Rollover');
        if (required($taxCertPdf, $used))
          throw invalidMessage('tax_cert_pdf.related', 'Amount Used');
        if (required($tccPin, $taxCertPdf))
          throw invalidMessage('tcc_pin.required');
        if (required($date, $taxCertPdf))
          throw invalidMessage('date.required');

      } // if ( ! $overrideValidation )


      // Ensure we have a valid client
      $client = $app->db->table('clients')->where('client_id', $clientUid)->getFirst();
      if (!$client)
        throw invalidMessage('client_id.notfound');

      // Get current TCC if it exists + Check if the TCC PIN is not already used
      if ($tccPin) {
        $tccBefore = $app->db->table('tccs')->where('tcc_pin', $tccPin)->getFirst();
        if (!$overrideValidation and $tccBefore and $tccBefore->id != $id) {
          throw invalidMessage('tcc_pin.exists', $tccBefore->id);
        }
      }

      if (!$tccBefore) {
        $tccBefore = $tccModel->getTccById($id); // Returns a blank, but valid, TCC if $id = 'new' or <= 0
        // $tccBefore will only be null if $id > 0, but the TCC doesn't exist
        if (!$tccBefore)
          throw new Error("TCC id=$id not found.");
      }

      debug_log($tccBefore, 'tccBefore = ', 3);


      // Deep Validate Allocations...
      debug_log($tccAmount, 'amount_cleared: ', 2);
      debug_log($rollover, 'rollover: ', 2);
      debug_log($reserved, 'amount_reserved: ', 2);
      debug_log($remaining, 'amount_remaining: ', 2);
      debug_log($available, 'amount_available: ', 2);
      debug_log($used, 'amount_used: ', 2);

      $clearedNet = $tccAmount - $reserved;
      debug_log($clearedNet, 'amount cleared net (calc): ', 2);

      $updatedAllocs = json_decode($allocatedTradesJson ?: '{}', true);
      debug_log($updatedAllocs, 'new allocs: ', 2);

      if (!is_array($updatedAllocs))
        $updatedAllocs = [];

      $updatedAllocsBreakdown = getAllocsBreakdown($app, $updatedAllocs, true);
      debug_log($updatedAllocsBreakdown, 'new allocs breakdown: ', 4);

      $updatedTotalUsed = $updatedAllocsBreakdown['totalUsed'] ?? 0;
      $updatedTotalUsedByYear = $updatedAllocsBreakdown['totalUsedByYear'] ?? [];
      $updatedAllocatedTradesByYear = $updatedAllocsBreakdown['tradesByYear'] ?? [];
      $updatedAllocatedTrades = $updatedAllocsBreakdown['trades'] ?? [];

      debug_log($updatedTotalUsed, 'new total allocated/used: ', 2);
      debug_log($updatedTotalUsedByYear, 'new total allocated/used by year: ', 2);
      debug_log($updatedAllocatedTradesByYear, 'updated allocated trades by year: ', 4);
      debug_log($updatedAllocatedTrades, 'updated allocated trades: ', 2);

      if ($updatedTotalUsed > $clearedNet and !$overrideValidation) {
        throw invalidMessage('allocated_trades.over');
      }


      // ---------
      // Save TCC
      // ---------

      $app->db->pdo->beginTransaction();
      debug_log('Save TCC: DB Transaction started.', '', 2);

      $saveOptions = ['autoStamp' => true, 'user' => $app->user->user_id];

      // Try to resolve allocation relationships and update related items if the allocations string changed.
      // Welcome to the jungle... Good luck :)
      if ($allocatedTradesJson != $tccBefore->allocated_trades) {

        $currentAllocs = json_decode($tccBefore->allocated_trades ?: '{}', true);
        debug_log($currentAllocs, 'current allocs: ', 2);

        if (!is_array($currentAllocs))
          $currentAllocs = [];

        unset($currentAllocs['_FIX_']); // Not really used anymore... remove?

        $currentAllocsBreakdown = getAllocsBreakdown($app, $currentAllocs);
        debug_log($currentAllocsBreakdown, 'current allocs breakdown: ', 4);

        $currentTotalUsed = $currentAllocsBreakdown['totalUsed'] ?? 0;
        $currentTotalUsedByYear = $currentAllocsBreakdown['totalUsedByYear'] ?? [];
        $currentTradesByYear = $currentAllocsBreakdown['tradesByYear'] ?? [];
        $currentAllocatedTrades = $currentAllocsBreakdown['trades'] ?? [];

        debug_log($currentTotalUsed, 'total used (current): ', 2);
        debug_log($currentTotalUsedByYear, 'total used by year (current): ', 2);
        debug_log($currentTradesByYear, 'current trades by year: ', 4);
        debug_log($currentAllocatedTrades, 'current allocated trades: ', 2);

        $tradeModel = new TradeModel($app);

        foreach ($updatedAllocs as $tradeId => $coverAmount) {
          $trade = array_find($updatedAllocatedTrades, 'trade_id', $tradeId);
          if (!$trade)
            throw new Exception("Removed trade $tradeId not found in current allocated trades.");
          $tradePins = json_decode($trade->allocated_pins ?: '{}', true);
          if (!is_array($tradePins))
            $tradePins = [];
          $currentCover = floatval($tradePins[$tccPin] ?? 0);
          // Replace the current cover allocated with the new allocated value.
          $trade->amount_covered = floatval($trade->amount_covered) - $currentCover + $coverAmount;
          $tradePins[$tccPin] = $coverAmount;
          $trade->allocated_pins = json_stringify($tradePins);
          $tradeModel->save((array) $trade);
        }

        $removedAllocs = array_diff_key($currentAllocs, $updatedAllocs);
        debug_log($removedAllocs, 'removed allocs: ', 2);

        foreach ($removedAllocs as $tradeId => $coverAmount) {
          $trade = array_find($currentAllocatedTrades, 'trade_id', $tradeId);
          if (!$trade)
            throw new Exception("Removed trade $tradeId not found in current allocated trades.");
          $tradePins = json_decode($trade->allocated_pins ?: '{}', true);
          if (!is_array($tradePins))
            $tradePins = [];
          $currentCover = floatval($tradePins[$tccPin] ?? 0);
          $trade->amount_covered = floatval($trade->amount_covered) - $currentCover;
          unset($tradePins[$tccPin]);
          $trade->allocated_pins = json_stringify($tradePins);
          $tradeModel->save((array) $trade);
        }

      }

      debug_log($forceUsed ? 'Y' : 'N', 'force amount used: ', 2);
      if (!$forceUsed) {
        if (isset($rollover)) {
          $usedAfterRollover = $updatedTotalUsedByYear[$tccYear + 1] ?? 0;
          debug_log($usedAfterRollover, 'used after rollover (calc): ', 2);
          $used = $rollover + $usedAfterRollover;
        } else {
          $used = $updatedTotalUsed;
        }
      }

      $remaining = $clearedNet - $used;
      debug_log($remaining, 'amount remaining (calc): ', 2);

      $available = ($status == 'Approved') ? $remaining : 0;
      debug_log($available, 'amount available (calc): ', 2);

      $expiredIn = null;
      if ($isExpired or $status == 'Expired' or ($status == 'Approved' and !$available)) {
        $expiredIn = ($rollover > 0) ? $tccYear + 1 : $tccYear;
      }
      debug_log($expiredIn, 'expired in (calc): ', 2);

      $_POST['rollover'] = $rollover;
      $_POST['amount_cleared'] = $tccAmount;
      $_POST['amount_cleared_net'] = $clearedNet;
      $_POST['amount_remaining'] = $remaining;
      $_POST['amount_available'] = $available;
      $_POST['amount_used'] = $used;

      $_POST['expired'] = $expiredIn;

      $_POST['sync_at'] = $now;
      $_POST['sync_by'] = $app->user->user_id;
      $_POST['sync_from'] = 'local';
      $_POST['sync_type'] = $isEdit ? 'update' : 'new';

      $_POST['tax_cert_pdf'] = trim($tccPin) . '.pdf';

      $saveTccResult = $tccModel->save($_POST);
      if ($isNew and !$saveTccResult)
        throw new Exception('Failed to save new TCC.');
      debug_log($saveTccResult, 'Save TCC Result: ', 2);

      $savedTccId = $saveTccResult['id'] ?? null;

      if (!$savedTccId)
        throw new Exception('Failed to save TCC. No ID returned after save.');


      // ---------------
      // Process Uploads
      // ---------------

      // This section needs to be AFTER saving!
      // We need a new client's ID to get a save dir.
      $saveDir = $app->uploadsDir . __DS__ . $client->name . '_' . $client->id;

      $tccFiles = ['id' => $savedTccId];

      $certFileBase = trim($tccPin);
      $tccFiles['tax_cert_pdf'] = process_upload(
        'tax_cert_pdf_file',
        $tccBefore->tax_cert_pdf ?? '',
        $taxCertPdf,
        $saveDir,
        $certFileBase
      );

      // Update the client's upload field values after processing.
      $updateTccFilesResult = $tccModel->update($tccFiles);
      debug_log($updateTccFilesResult, 'Update TCC Files Result: ', 2);


      // ------------------------
      // End Save TCC Transaction
      // ------------------------

      $app->db->pdo->commit();
      debug_log('Save TCC: DB Transaction committed.', 2);


      // -----------------
      // Update Remote: S2
      // -----------------

      $userEmail = 'neels@currencyhub.co.za';

      // Get the latest version of the TCC, as saved in the DB
      $tcc = $tccModel->getTccById($savedTccId);
      debug_log($tcc, 'Just Saved TCC = ', 2);

      if (!$tcc or !$tcc->id)
        throw new Exception("TCC $savedTccId not found.");

      $tccS2Mapper = new TccS2MapperModel();

      $tccHasRelevantChanges = $isEdit and (
        $tccBefore->date != $tcc->date ||
        $tccBefore->status != $tcc->status ||
        $tccBefore->expired != $tcc->expired ||
        $tccBefore->rollover != $tcc->rollover ||
        $tccBefore->amount_cleared != $tcc->amount_cleared ||
        $tccBefore->amount_reserved != $tcc->amount_reserved ||
        $tccBefore->allocated_trades != $tcc->allocated_trades ||
        $tccBefore->tcc_pin != $tcc->tcc_pin
      );

      // Choose remote update extent...
      if ($isNew or $tccHasRelevantChanges) {

        // First, update and calculate everything affected by this TCC locally
        $clientStateModel = new ClientStateModel($app);
        $updateResult = $clientStateModel->updateStateFor($client);
        debug_log($updateResult, 'Client Update State Result: ', 3);

        debug_log(PHP_EOL, '', 2);
        debug_log('Sync client state changes with S2 (Google Sheets)', 2);

        // Then, sync all the local changes with S2 (Google Sheets)
        // We exclude statement data to prevent generating a new statement.
        // TCC's don't affect a client's statement lines.
        $responseModel = new ClientS2ResponseModel($app, ['no-statement' => true]);
        $responseModel->add('tccData', $tccS2Mapper->mapTccToRemoteRow($tcc));
        $payload = $responseModel->generate($updateResult);

        run_google_script('submitTcc', $payload, $userEmail);

      } else {

        // Less comprehensive S2 update when we just want to change the note, caseno, files, etc...

        debug_log(PHP_EOL, '', 2);
        debug_log('Upsert TCC changes to S2 (Google Sheets)', 2);

        // Only Upsert TCC data, don't update calculated values or statements.
        // PS: Client side should check and prevent save if no changes were made
        $payload = [
          'sheetName' => __GOOGLE_TCCS_SHEET_NAME__,
          'primaryKey' => 'TCC ID',
          'row' => $tccS2Mapper->mapTccToRemoteRow($tcc)
        ];

        run_google_script('upsertRow', $payload, $userEmail);

      }


      json_response(['success' => true, 'id' => $savedTccId, 'goto' => 'back']);

    } // saveTcc



    /** DEFAULT ACTION **/

    json_response(['success' => false, 'message' => 'Invalid request']);

  } catch (ValidationException $ex) {
    $errors = $ex->getErrors();
    debug_log($errors, 'Validation Exception: ');
    json_response(['success' => false, 'message' => $ex->getMessage(), 'errors' => $errors]);
  } catch (Exception $ex) {
    $error = $ex->getMessage();
    $app->logger->log($error, 'error');
    $app->db->safeRollBack();
    abort_uploads();
    json_response(['success' => false, 'message' => $error]);
  }

} // if ( $app->request->isPost )



// ---------
// -- GET --
// ---------

$form = new AppForm();

$tccModel = new TccModel($app);
$tcc = $tccModel->getTccById($id);
if (!$tcc)
  respond_with("TCC id=$id not found.", 404);
debug_log($tcc, 'Editing tcc: ', 2);

if ($isNew)
  $tcc->client_id = $_GET['client'] ?? '';

// for status dropdown
$statuses = array('Pending', 'Awaiting Docs', 'Approved', 'Declined', 'Expired', 'Used');

// for clients dropdown
$accountant = 'All';
$clientModel = new ClientModel($app);
$clients = $clientModel->getAllByAccountant($accountant);

$super = in_array($app->user->role, ['super-admin', 'accountant']);