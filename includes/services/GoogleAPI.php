<?php namespace App\Services;

use Exception;
use Firebase\JWT\JWT;

/**
 * GoogleAPI Class - 20 Jun 2023
 * 
 * @author Neels Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.7 - FT - 02 May 2024
 *  - Add the correct permission scopes for sending emails via API call.
 * 
 * TODO: Look into not using the full permissions stack for every call.
 */

class GoogleAPI {

  const GAPI3_FILES   = 'https://www.googleapis.com/drive/v3/files/';
  const GAPI4_SHEETS  = 'https://sheets.googleapis.com/v4/spreadsheets/';
  const GAPI1_SCRIPTS = 'https://script.googleapis.com/v1/scripts/';

  const SCOPE_DRIVE    = 'https://www.googleapis.com/auth/drive';
  const SCOPE_SHEETS   = 'https://www.googleapis.com/auth/spreadsheets';
  const SCOPE_PROJECTS = 'https://www.googleapis.com/auth/script.projects';

  const SCOPE_SENDMAIL = 'https://www.googleapis.com/auth/gmail.settings.basic ' .
                         'https://www.googleapis.com/auth/gmail.readonly ' .
                         'https://www.googleapis.com/auth/gmail.modify ' . 
                         'https://mail.google.com/';

  protected $options;

  private $httpClient;
  private $privateKey;
  private $serviceAccountEmail;
  private $accessToken;
  private $expiresAt;
  private $scope;


  public function __construct( $httpClient, $privateKey, $userEmail, $options = [] ) {
    $this->httpClient = $httpClient;
    $this->privateKey = $privateKey;
    $this->serviceAccountEmail = $userEmail;

    $authInfo = $this->getAuthInfo();
    $this->accessToken = $options['access_token'] ?? $authInfo['access_token'];
    $this->expiresAt = $options['expires_at'] ?? $authInfo['expires_at'];
    $this->scope = $options['scope'] ?? $authInfo['scope'];

    $this->options = $options;
  }


  public function getSheetsUrl( $spreadsheetId, $range ) {
    return self::GAPI4_SHEETS . $spreadsheetId . '/values/' . urlencode($range);
  }


  public function authenticate( $scope = null, $userEmail = null ) {

    $now = time();

    if ( ! $this->accessToken or $this->scope != $scope or $this->expiresAt < $now ) {

      // Forget any old info.
      $this->forgetAuthInfo();

      $payload = [
        'iat' => $now,
        'exp' => $now + 3600,
        'iss' => $this->serviceAccountEmail,
        'sub' => $userEmail ?: $this->serviceAccountEmail,
        'aud' => 'https://oauth2.googleapis.com/token',
        'scope' => $scope,
      ];

      $jwt = JWT::encode( $payload, $this->privateKey, 'RS256' );

      $response = $this->httpClient->post( 'https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
      ] );

      $accessTokenData = json_decode( $response, true );
      
      if ( empty( $accessTokenData['access_token'] ) ) {
        throw new Exception( 'Authentication failed: ' . json_encode( $accessTokenData ) );
      }

      $this->scope = $scope;

      $this->accessToken = $accessTokenData['access_token'];

      $this->expiresAt = time() + $accessTokenData['expires_in'];

      // Stash to session store.
      $this->rememberAuthInfo( $this->accessToken, $this->expiresAt, $this->scope );
    
    }

  } // authenticate


  public function getAuthInfo() {
    return [
      'access_token' => $_SESSION['access_token'] ?? null,
      'expires_at' => $_SESSION['access_expires'] ?? null,
      'scope' => $_SESSION['access_scope'] ?? null,
    ];
  }


  public function forgetAuthInfo() {
    if ( session_status() == PHP_SESSION_ACTIVE ) {
      unset( $_SESSION['access_token'] );
      unset( $_SESSION['access_expires'] );
      unset( $_SESSION['access_scope'] );
    }
  }


  public function rememberAuthInfo( $token, $expiresAt, $scope ) {
    if ( session_status() == PHP_SESSION_ACTIVE ) {
      $_SESSION['access_token'] = $token;
      $_SESSION['access_expires'] = $expiresAt;
      $_SESSION['access_scope'] = $scope;
    }
  }


  public function fetchSpreadsheetData( $spreadsheetId, $range ) {

    try {

      $this->authenticate( self::SCOPE_SHEETS . '.readonly' );

      $url = $this->getSheetsUrl( $spreadsheetId, $range );

      $headers = [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Accept' => 'application/json',
      ];

      $jsonResp = $this->httpClient->get( $url, $headers );

      if ( empty( $jsonResp ) ) throw new Exception( 'No response.' );

      debug_log( substr( $jsonResp, 0, 100 ), 'fetchSpreadsheetData(), jsonResp: ', 3 );

      $response = json_decode( $jsonResp, true );

      if ( ! $response or ! is_array( $response ) or isset( $response['error'] ) )
        throw new Exception( isset( $response['error'] )
          ? $response['error']['message']
          : print_r( $jsonResp, true )
        );

      return $response;
    }
    
    catch ( Exception $e ) {
      $message = 'Failed to fetch spreadsheet data: ' . $e->getMessage();
      throw new Exception( $message );
    }

  } // fetchSpreadsheetData


  public function saveSpreadsheetData( $spreadsheetId, $range, array $values, $userEmail = null ) {

    try {

      $this->authenticate( self::SCOPE_SHEETS, $userEmail );

      $url = $this->getSheetsUrl( $spreadsheetId, $range ) . ':append?valueInputOption=RAW';

      $data = json_encode([
        'range' => $range,
        'majorDimension' => 'ROWS',
        'values' => $values,
      ]);

      $headers = [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ];

      // Making a POST request to the Google Sheets API
      $response = $this->httpClient->post( $url, $data, $headers );

      if ( empty( $response ) ) throw new Exception( 'Error: No response.' );

      return json_decode( $response, true );
    }

    catch (Exception $e) {
      throw new Exception( 'Failed to save spreadsheet data: ' . $e->getMessage() );
    }

  } // saveSpreadsheetData


  public function saveSpreadsheetRow( $spreadsheetId, $sheetName, array $headers, 
    array $newRow, $primaryKey ) {

    try {

      // Fetch existing data
      $data = $this->fetchSpreadsheetData( $spreadsheetId, $sheetName . '!A1:Z' );
      
      // Check if data contains any rows
      if ( ! isset( $data['values'] ) or count( $data['values'] ) <= 1 ) {
        // No existing data (ignoring header row), append the new row
        $this->saveSpreadsheetData( $spreadsheetId, $sheetName . '!A1', [$headers, $newRow] );
        return;
      }

      // Look for the primary key in the headers
      $pkIndex = array_search( $primaryKey, $headers );
      if ( $pkIndex === false ) throw new Exception( 'Primary key not found in headers' );

      // Check for existing row with the same primary key
      $existingRow = null;
      foreach ( $data['values'] as $index => $row ) {
        if ( $index == 0 ) continue; // Skip header row
        if ( isset( $row[$pkIndex] ) and $row[$pkIndex] == $newRow[$pkIndex] ) {
          $existingRow = $index;
          break;
        }
      }

      if ( $existingRow !== null ) {
        // Existing row found, update it
        $url = $this->getSheetsUrl( $spreadsheetId, $sheetName . '!A' . ( $existingRow + 1 ) );

        $headers = [
          'Authorization' => 'Bearer ' . $this->accessToken,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ];

        $data = json_encode( [ 'values' => [ $newRow ] ] );

        $this->httpClient->post( $url, $data, $headers );

      }
      // Else: append new row
      else $this->saveSpreadsheetData( $spreadsheetId, $sheetName . '!A1', [ $newRow ] );

    }

    catch ( Exception $e ) {
      $message = 'Failed to save spreadsheet row: ' . $e->getMessage();
      throw new Exception( $message );
    }

  } // saveSpreadsheetRow


  public function callAppsScript( $scriptId, $functionName, $parameters, $userEmail = null ) {

    debug_log( compact( 'scriptId', 'functionName', 'userEmail' ),
      'callAppsScript(), params: ', 2 );

    debug_log( $parameters, 'callAppsScript(), parameters: ', 4 );

    try {
      
      $permissionScopes = [
        self::SCOPE_DRIVE,
        self::SCOPE_SHEETS,
        self::SCOPE_PROJECTS,
        self::SCOPE_SENDMAIL
      ];

      $this->authenticate( implode( ' ', $permissionScopes ), $userEmail );

      $url = self::GAPI1_SCRIPTS . $scriptId . ':run';

      $data = json_encode( [
        'function' => $functionName,
        'parameters' => $parameters
      ] );

      debug_log( $data, 'Google::callAppsScript(), post data: ', 4 );

      $headers = [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ];

      // Make a POST request to the Apps Script API
      $response = $this->httpClient->post( $url, $data, $headers );

      if ( empty( $response ) ) throw new Exception( 'Error: No response.' );

      return json_decode( $response, true );

    }

    catch ( Exception $e ) {
      $message = 'Failed to call Apps Script: ' . $e->getMessage();
      throw new Exception( $message );
    }

  } // callAppsScript


  public function validateAppScriptCallResponse( $response ) {

    debug_log( $response, 'validateAppScriptCallResponse(), response: ', 2 );

    if ( ! $response )
      throw new Exception( 'Error: Apps Script call failed. No response.' );

    if ( ! isset( $response['done'] ) or $response['done'] !== true )
      throw new Exception( 'Error: Apps Script call failed.' );

    if ( ! isset( $response['response']['result'] ) )
      throw new Exception( 'Error: Apps Script call failed. No result.' );

    if ( isset( $response['response']['result']['error'] ) )
      throw new Exception( 'Error: Apps Script call failed. ' . 
        $response['response']['result']['error']['message'] );

    $resultRaw = $response['response']['result'];

    if ( is_numeric( $resultRaw ) )
      return [ 'success' => true, 'message' => 'Success: Last row = ' . $resultRaw ];

    $result = json_decode( $resultRaw, true );

    if ( ! $result )
      throw new Exception( 'Apps Script call failed. result = ' . print_r( $result, true ) );

    if ( ! $result[ 'success' ] )
      throw new Exception( 'Apps Script call failed. message = ' . $result[ 'message' ] );

    return $result;

  } // validateAppScriptCallResponse


  public function downloadPdfFromDrive( $fileId, $filename = 'file', $userEmail = null ) {

    try {

      $this->authenticate( self::SCOPE_DRIVE, $userEmail );

      $url = self::GAPI3_FILES . $fileId . '?alt=media';

      $headers = [ 'Authorization' => 'Bearer ' . $this->accessToken ];

      $response = $this->httpClient->get( $url, $headers );

      if ( empty( $response ) )
        throw new Exception( 'Error: No response.' );

      $contentLength = strlen($response);

      if ( $contentLength < 1024 )
        throw new Exception( 'Invalid response: ' . $response );

      // Send headers and the file to the client
      header( 'Content-Description: File Transfer' );
      header( 'Content-Type: application/octet-stream' );
      header( 'Content-Disposition: attachment; filename=' . $filename . '.pdf' );
      header( 'Expires: 0' );
      header( 'Cache-Control: must-revalidate' );
      header( 'Pragma: public' );
      header( 'Content-Length: ' . strlen( $response ) );
      echo $response;

    }

    catch ( Exception $e ) {
      $message = 'Failed to download PDF from Google Drive: ' . $e->getMessage();
      throw new Exception( $message );
    }

  } // downloadPdfFromDrive

} // GoogleAPI