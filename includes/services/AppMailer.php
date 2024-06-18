<?php namespace App\Services;

use Exception;
use PHPMailer\PHPMailer;

/**
 * App Mailer Class - 18 Jan 2017
 *   - A custom PHPMailer wrapper to send SMTP emails with attachments and/or embedded images.
 *
 * @author C. Moller <xavier.tnc@gmail.com>
 *
 * @version 1.0 - FT - 02 April 2018
 *   - Upgrade PHPMailer version from 5.2 to 6.2
 *   - Add email validation before send.
 *   - Add encryption option
 *
 * @version 1.1 - FT - 20 April 2018
 *   - Add 3rd BCC option
 *   - Add 'Debugoutput' handler
 *   - Add removeBadContent()
 *   - Add $mailer->SMTPOptions comment
 *
 * @version 1.2 - FT - 02 May 2018
 *   - Add Mailer::init()
 *   - Add support for Mailer::send()/init() $options = ['debugLevel' => 2]
 *   - Add support for Mailer::send()/init() $options = ['throwExceptions' => true]
 *   - Add support for Mailer::send() $options = ['forceInitMailer' => true]
 *   - Add support for Mailer::send() $options = ['baseDir' => '/some/asb/path/to/webroot']
 *   - Mailer now defaults to NOT throwing exceptions.
 *   - Restructured code and added many usage comments.
 * 
 * @version 2.0 - UPD - 19 Mar 2024
 *   - Adapt this old KD class for use with MyHub App.
 *   - Convert static class methods to standard methods.
 */

class AppMailer {

  private $app;
  private $from;
  private $fromName;
  private $bcc;
  private $bccName;
  private $mailer;
  

  public function __construct( $app, $options = [] ) {
    debug_log( $options, 'AppMailer::__construct(), options: ', 2 );
    $this->app = $app;
    $this->mailer = new PHPMailer( 'throw exceptions' );
    if ( ! $this->mailer ) throw new Exception( 'AppMailer::__construct(), Failed', 3 );
    $this->init( $options );
  }


  public function init( $options = [] ) {

    $mailer = $this->mailer;

    if ( $options['SMTP'] ?? true )
    {
      $mailer->isSMTP();
    } else {
      $mailer->isMail(); // Use PHP mail() function
    }

    // Debug level:
    // 0 == off
    // 1 == client requests
    // 2 == client requests + server responses
    // 3 + 4 == more and more detail (connection status etc.)
    $mailer->SMTPDebug = $options['SMTPDebug'] ?? 0;

    // Redirect Mailer logs to our own Logger service.
    $mailer->Debugoutput = function( $str, $level ) {
      debug_log( "Debug level $level; Message: $str" );
    };

    $mailer->Host = $options['Host'] ?? __SMTP_HOST__;
    $mailer->Port = $options['Port'] ?? __SMTP_PORT__; // 25(plain), 587(tls), 465(ssl)

    $mailer->SMTPAuth = $options['SMTPAuth'] ?? true;
    $mailer->SMTPSecure = $options['SMTPSecure'] ?? __SMTP_ENCR__; // false, 'tls' or 'ssl'

    $mailer->Username = $options['Username'] ?? __SMTP_USER__;
    $mailer->Password = $options['Password'] ?? __SMTP_PASS__;

    $mailer->CharSet = $options['CharSet'] ?? 'UTF-8';

    $this->from = $options['From'] ?? $mailer->Username;
    $this->fromName = $options['FromName'] ?? 'MyHub';

    $this->bcc = $options['Bcc'] ?? null;
    $this->bccName = $options['BccName'] ?? 'Bcc';

  } // init


  public function getLastError() {
    return $this->mailer->ErrorInfo;
  }


  /**
   * @param string validationMethod Values: pcre8, html5, php
   */
  public function validateAddress( $address, $validationMethod = 'php' ) {
    return PHPMailer::validateAddress( $address, $validationMethod );
  }


  public function removeBadContent( $unsafeString = null ) {
    $str = preg_replace( '/\x00|<[^>]*>?/', '', trim( $unsafeString ?: '' ) );
    return str_replace( [ "'", '"' ], '`', $str );
  }


  /**
   * Send Mail
   *
   * For more info goto: https://github.com/PHPMailer/PHPMailer/wiki/Tutorial
   *
   * Address Validation:
   *  All PHPMailer functions that accept an email address, like addAddress() applies validation and will
   *  return TRUE if the address was accepted or FALSE if not. Failures will also set Mailer::$lastError or
   *  throw an exception if exceptions are enabled.
   *
   * Embedded Images:
   *  To embed images, use $mailer->msgHTML($message,$baseDir) to AUTO PARSE the message and SETUP Mailer.
   *  If you define $baseDir when calling msgHTML(), relative <img> tags in your message will automatically be
   *  updated to include the base directory. For example: <img src="img/someimage.jpg" alt="Embedded image">
   *  with $baseDir = '/var/www/html/mysite/' will convert to <img src="/var/www/html/mysite/img/someimage.jpg" ...>
   *  If $baseDir is undefined, paths must be ABSOLUTE or relative to your script path, NOT the Mailer class path!
   *
   * File Attachments:
   *  e.g $mail->addAttachment($path, $name, $encoding, $type);
   *
   * String Attachments:
   *  Just like addAttachment(), but you provide the content directly, NOT just a local filepath.
   *  e.g. $mailer->addStringAttachment(file_get_contents($url), 'myfile.pdf');
   *  Useful if you want to send database blobs or other dynamically created content.
   *
   * We can set/override security settings in the following way:
   *   $mail->Host = 'tls:*smtp.gmail.com:587';
   *   $mail->Host = 'ssl:*smtp.gmail.com:465';
   *
   * Opportunistic TLS:
   *  PHPMailer 5.2.10 introduced opportunistic TLS - if it sees that the server is
   *  advertising TLS encryption(after you have connected to the server), it enables encryption automatically,
   *  even if you have not set SMTPSecure. This might cause issues if the server is advertising TLS with an
   *  invalid certificate, but you can turn it off with $mailer->SMTPAutoTLS = false;
   *
   * PS: Remember to sanitize $htmlMessage content sourced from user input! @see Mailer::removeBadContent()
   * PS: This service won't throw any exceptions unless we set the 'throwExceptions' option.
   *
   * @return boolean
   *    true  == OK
   *    false == ERROR (with the error message in Mailer::$lastError)
   */
  public function send( $to = null, $subject = null, $htmlMessage = null, $attachments = null, $options = [] ) {

    $mailer = $this->mailer;

    if ( isset( $options['SMTPDebug'] ) ) {
      $mailer->SMTPDebug = $options['SMTPDebug'];
    }

    // Avoid unintended recipients and/or attachments
    $mailer->clearAllRecipients();
    $mailer->clearAttachments();

    $stringEmbeddedImages = $options['Images'] ?? [];
    foreach ( $stringEmbeddedImages as $uid => $imageData )
    {
      $mailer->addStringEmbeddedImage( $imageData, $uid, "$uid.png", 'base64', 'image/png' );
    }

    foreach ( $attachments ?: [] as $attachment )
    {
      if ( is_object( $attachment ) ) $attachment = (array) $attachment;
      if ( ! is_array( $attachment ) ) continue;
      $mailer->addAttachment(
        $attachment['path'],
        $attachment['name'] ?? basename( $attachment['path'] ),
        $attachment['encoding'] ?? 'base64',
        $attachment['type'] ?? 'application/octet-stream'
      );
    }

    // NOTE: All types of addresses are automatically
    // validated and will report errors if not valid.
    $mailer->addAddress( $to );
    $mailer->setFrom( $options['From'] ?? $this->from, $options['FromName'] ?? $this->fromName );
    $mailer->addReplyTo( $options['ReplyTo'] ?? $this->from, $options['ReplyName'] ?? $this->fromName );

    if ( isset( $options['Cc'] ) )
      $mailer->addCC( $options['Cc'], $options['CcName'] ?? 'Cc' );
  
    $bcc = $options['Bcc'] ?? $this->bcc;
    if ( $bcc ) $mailer->addBCC( $bcc, $options['BccName'] ?? $this->bccName );

    if ( isset( $options['Bcc2'] ) )
      $mailer->addBCC( $options['Bcc2'], $options['BccName2'] ?? 'Bcc2' );

    if ( isset( $options['Bcc3'] ) )
      $mailer->addBCC( $options['Bcc3'], $options['BccName3'] ?? 'Bcc3' );

    $mailer->Subject = $subject;

    // We use $mailer->msgHTML($htmlMessage, $baseDir) to automatically
    // inline <img> tags and set $mailer->AltBody to the plain-text version of $htmlMessage.
    // $baseDir is an absolute (local filesystem) path to prepend to any relative paths.
    $mailer->msgHTML( $htmlMessage, $options['BaseDir'] ?? null );

    return $mailer->send();

  } // send

} // AppMailer