<?php namespace App\Models;

use stdClass;


class UserSettings {

  private $app;

  public $userId;
  public $settings;


  public function __construct( $app, $userId = null )
  {
    $this->app = $app;
    $this->userId = $userId ?? $app->user->id;
    $this->settings = $this->getSettings();
  }


  public function newSetting( $settingName, $settingValue = null )
  {
    $setting = new stdClass();
    $setting->user_id = $this->userId;
    $setting->setting_key = $settingName;
    $setting->setting_value = $settingValue;
    return $setting;
  }


  public function getSettings()
  {
    $results = $this->app->db->table( 'user_settings' )
      ->where( 'user_id', '=',  $this->userId )
      ->orderBy( 'setting_key' )
      ->getAll();
    return array_column( $results, null, 'setting_key' );
  }


  public function getSetting( $settingName, $default = null )
  {
    return empty( $this->settings[$settingName] )
     ? $this->newSetting( $settingName, $default )
     : $this->settings[$settingName];
  }


  public function getSettingValue( $settingName, $default = null )
  {
    return empty( $this->settings[$settingName] )
     ? $default
     : $this->settings[$settingName]->setting_value;
  }


  public function saveSetting( $setting )
  {
    $options = [ 'autoStamp' => true ];
    return $this->app->db->table( 'user_settings' )
      ->save( (array) $setting, $options );
  }


  public function saveIfChanged( $settingName, $newValue )
  {
    $setting = $this->getSetting( $settingName );
    if ( $setting->setting_value != $newValue ) {
      $setting->setting_value = $newValue;
      return $this->saveSetting( $setting );
    }
  }

} // UserSettings


// CREATE TABLE `user_settings` (
//   `id` int(11) NOT NULL AUTO_INCREMENT,
//   `user_id` int(11) NOT NULL,
//   `setting_key` varchar(255) NOT NULL,
//   `setting_value` varchar(255) NOT NULL,
//   `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
//   `updated_at` datetime DEFAULT NULL,
//   PRIMARY KEY (`id`),
//   KEY `user_id` (`user_id`),
// ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
