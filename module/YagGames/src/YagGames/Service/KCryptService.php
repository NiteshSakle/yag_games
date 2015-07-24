<?php

namespace YagGames\Service;

class KCryptService
{

  private $settingsTable;
  private $settings;
  private $gameConfig;

  public function setSettingsTable($settingsTable)
  {
    $this->settingsTable = $settingsTable;
  }

  public function setConfig($config)
  {
    $this->gameConfig = $config;
    require_once $this->gameConfig['main_site']['path'] . "/assets/includes/enc.functions.php";
  }

  public function enc($string)
  {
    global $config;

    $config['settings'] = $this->getSettings();
    return \k_encrypt($string);
  }

  public function dec($string)
  {
    global $config;
    
    $config['settings'] = $this->getSettings();
    return \k_decrypt($string);
  }

  public function getSettings()
  {
    if (!isset($this->settings)) {
      $this->settings = $this->settingsTable->fetchAll();
    }

    return $this->settings;
  }

}
