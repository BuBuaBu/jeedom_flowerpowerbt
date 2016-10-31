<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
if (!class_exists('fpParrotAPI')) { require_once dirname(__FILE__) . '/../../3rdparty/flowerpower.php'; }

class flowerpowerbt extends eqLogic {
  public static $_widgetPossibility = array('custom' => true);

  public static function cronHourly() {
    log::add('flowerpowerbt', 'debug', 'cronHourly');
    flowerpowerbt::getGarden();
    flowerpowerbt::getFlower();
    //flowerpowerbt::scanFlower();
  }

  public static function cronDaily() {
    config::save('refresh_token', '',  'flowerpowerbt');
  }

  public static function dependancy_info() {
    $return = array();
    $return['log'] = 'flowerpowerbt_dep';
    $flowerapi = realpath(dirname(__FILE__) . '/../../node/node_modules/');
    $return['progress_file'] = '/tmp/flowerpowerbt_dep';
    if (is_dir($flowerapi)) {
      $return['state'] = 'ok';
    } else {
      $return['state'] = 'nok';
    }
    $return['launchable'] = 'ok';
    return $return;
  }

  public static function dependancy_install() {
    log::add('flowerpowerbt','info','Installation des dépendances nodejs');
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('flowerpowerbt_dep') . ' 2>&1 &');
    flowerpowerbt::doConf();
  }

  public static function scanFlower() {
    if (config::byKey('cloudActive', 'flowerpowerbt') == '1') {
      $sensor_path = realpath(dirname(__FILE__) . '/../../node');
      $port = str_replace('hci', '', jeedom::getBluetoothMapping(config::byKey('port', 'flowerpowerbt',0)));
      $cmd = 'cd ' . $sensor_path . ' && sudo NOBLE_HCI_DEVICE_ID=' . $port . ' nodejs start.js';
      log::add('flowerpowerbt', 'debug', 'Lancement sync flowerpowerbt : ' . $cmd);
      exec($cmd . ' >> ' . log::getPathToLog('flowerpowerbt_node') . ' 2>&1 &');
   }
  }

  public static function doConf() {
    $clientID = config::byKey('clientID','flowerpowerbt');
    $clientSecret = config::byKey('clientSecret','flowerpowerbt');
    $userName = config::byKey('userName','flowerpowerbt');
    $passPhrase = config::byKey('passPhrase','flowerpowerbt');
    $sensor_path = realpath(dirname(__FILE__) . '/../../node/credentials.json');
    log::add('flowerpowerbt', 'debug', $sensor_path);
    $content = '{
      "client_id": "' . $clientID . '",
      "client_secret": "' . $clientSecret. '",
      "username": "' . $userName. '",
      "password": "' . $passPhrase. '",
      "url": "https://api-flower-power-pot.parrot.com"
    }';
    file_put_contents($sensor_path, $content);
    flowerpowerbt::getGarden();
    flowerpowerbt::getFlower();
    flowerpowerbt::scanFlower();
  }

  public static function getGarden() {
    log::add('flowerpowerbt', 'debug', 'Récupération garden');
    $clientID = config::byKey('clientID','flowerpowerbt');
    $clientSecret = config::byKey('clientSecret','flowerpowerbt');
    $userName = config::byKey('userName','flowerpowerbt');
    $passPhrase = config::byKey('passPhrase','flowerpowerbt');
    $access_token=config::byKey('access_token','flowerpowerbt');
    $refresh_token=config::byKey('refresh_token','flowerpowerbt');
    $expire_time=config::byKey('expire_time','flowerpowerbt');
    $flowerpower=new fpParrotAPI($clientID,$clientSecret,$userName,$passPhrase,$access_token, $refresh_token, $expire_time);
    if(is_object($flowerpower)){
      config::save('access_token', $flowerpower->access_token,  'flowerpowerbt');
      config::save('refresh_token', $flowerpower->refresh_token,  'flowerpowerbt');
      config::save('expire_time', $flowerpower->expire_time,  'flowerpowerbt');
    }

    $plants=$flowerpower->getPlants();
    //log::add('flowerpowerbt', 'debug', 'Garden ' . print_r($plants,true));

    foreach ($plants as $device) {
      //log::add('flowerpowerbt', 'debug', 'Garden ' . print_r($device,true));
      $flowerpowerbt = self::byLogicalId($device->location_identifier, 'flowerpowerbt');
      if (!is_object($flowerpowerbt)) {
        $flowerpowerbt = new flowerpowerbt();
        $flowerpowerbt->setEqType_name('flowerpowerbt');
        $flowerpowerbt->setLogicalId($device->location_identifier);
        $flowerpowerbt->setName('Flower - '. $device->location_identifier);
        $flowerpowerbt->setConfiguration('sensor_serial',$device->sensor->sensor_serial);
        $flowerpowerbt->setConfiguration('nickname',$device->sensor->sensor_identifier);
        $flowerpowerbt->setConfiguration('location_identifier',$device->location_identifier);
        $flowerpowerbt->setConfiguration('plant_nickname',$device->plant_nickname);
        $flowerpowerbt->setConfiguration('battery_type','1x AAA');
        $flowerpowerbt->save();
      } else {
        if ($device->sensor->nickname != $flowerpowerbt->getConfiguration('nickname')) {
          $flowerpowerbt->setConfiguration('nickname',$device->sensor->nickname);
          $flowerpowerbt->save();
        }
        if ($device->sensor->sensor_identifier != $flowerpowerbt->getConfiguration('plant_nickname')) {
          $flowerpowerbt->setConfiguration('plant_nickname',$device->plant_nickname);
          $flowerpowerbt->save();
        }
      }
      if (strpos($device->avatar_url,'http') === false) {
        $module=json_encode($device);
        $sensor=json_decode($module, true);
        $avatar_url = $sensor['pictures'][0]['url'];
      } else {
        $avatar_url = $device->avatar_url;
      }
      if ($avatar_url != $flowerpowerbt->getConfiguration('avatar_url')) {
        $flowerpowerbt->setConfiguration('avatar_url',$avatar_url);
        $flowerpowerbt->save();
      }
      if ($device->sensor->color == '6') {
        $color = 'vert';
      } else if ($device->sensor->color == '4') {
        $color = 'marron';
      } else {
        $color = $device->sensor->color;
      }
      if ($color != $flowerpowerbt->getConfiguration('color')) {
        $flowerpowerbt->setConfiguration('color',$color);
        $flowerpowerbt->save();
      }

      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'air_temperature');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Température de l\'Air');
        $cmdlogic->setLogicalId('air_temperature');
        $cmdlogic->setSubType('numeric');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'air_temperature_status');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Température de l\'Air - Statut');
        $cmdlogic->setLogicalId('air_temperature_status');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'air_temperature_instruction');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Température de l\'Air - Instruction');
        $cmdlogic->setLogicalId('air_temperature_instruction');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'soil_moisture');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Humidité du Sol');
        $cmdlogic->setLogicalId('soil_moisture');
        $cmdlogic->setSubType('numeric');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'soil_moisture_status');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Humidité du Sol - Statut');
        $cmdlogic->setLogicalId('soil_moisture_status');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'soil_moisture_instruction');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Humidité du Sol - Instruction');
        $cmdlogic->setLogicalId('soil_moisture_instruction');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'fertilizer');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Fertilisant');
        $cmdlogic->setLogicalId('fertilizer');
        $cmdlogic->setSubType('numeric');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'fertilizer_status');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Fertilisant - Statut');
        $cmdlogic->setLogicalId('fertilizer_status');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'fertilizer_instruction');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Fertilisant - Instruction');
        $cmdlogic->setLogicalId('fertilizer_instruction');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'light');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Luminosité');
        $cmdlogic->setLogicalId('light');
        $cmdlogic->setSubType('numeric');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'light_status');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Luminosité - Statut');
        $cmdlogic->setLogicalId('light_status');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($flowerpowerbt->getId(),'light_instruction');
      if (!is_object($cmdlogic)) {
        $cmdlogic = new flowerpowerbtCmd();
        $cmdlogic->setEqLogic_id($flowerpowerbt->getId());
        $cmdlogic->setEqType('flowerpowerbt');
        $cmdlogic->setType('info');
        $cmdlogic->setName('Luminosité - Instruction');
        $cmdlogic->setLogicalId('light_instruction');
        $cmdlogic->setSubType('string');
        $cmdlogic->save();
      }
    }
  }


  public static function getFlower() {
    log::add('flowerpowerbt', 'debug', 'Récupération valeurs');
    $clientID = config::byKey('clientID','flowerpowerbt');
    $clientSecret = config::byKey('clientSecret','flowerpowerbt');
    $userName = config::byKey('userName','flowerpowerbt');
    $passPhrase = config::byKey('passPhrase','flowerpowerbt');
    $access_token=config::byKey('access_token','flowerpowerbt');
    $refresh_token=config::byKey('refresh_token','flowerpowerbt');
    $expire_time=config::byKey('expire_time','flowerpowerbt');
    $flowerpower=new fpParrotAPI($clientID,$clientSecret,$userName,$passPhrase,$access_token, $refresh_token, $expire_time);
    if(is_object($flowerpower)){
      config::save('access_token', $flowerpower->access_token,  'flowerpowerbt');
      config::save('refresh_token', $flowerpower->refresh_token,  'flowerpowerbt');
      config::save('expire_time', $flowerpower->expire_time,  'flowerpowerbt');
    }

    $values=$flowerpower->getValues();
    //log::add('flowerpowerbt', 'debug', 'Values ' . print_r($values,true));

    foreach ($values as $mesure) {
      //log::add('flowerpowerbt', 'debug', 'Values ' . print_r($mesure,true));
      $module=json_encode($mesure);
      $flowerpower=json_decode($module, true);
      $flowerpowerbt = self::byLogicalId($flowerpower['location_identifier'], 'flowerpowerbt');
      $id = $flowerpowerbt->getId();
      $alert = str_replace('#','',$flowerpowerbt->getConfiguration('alert'));
      $flowerpowerbt->save();
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'air_temperature');
      $cmdlogic->setConfiguration('value', round($flowerpower['air_temperature']['gauge_values']['current_value'],2));
      $cmdlogic->save();
      $cmdlogic->event(round($flowerpower['air_temperature']['gauge_values']['current_value'],2));
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'air_temperature_status');
      $cmdlogic->setConfiguration('value', $flowerpower['air_temperature']['status_key']);
      if ($flowerpower['air_temperature']['status_key'] != 'status_ok') {
        if ($cmdlogic->getConfiguration('alert') == '0' && $alert != '') {
          $cmdlogic->setConfiguration('alert', '1');
          $cmdalerte = cmd::byId($alert);
          $options['title'] = "Alerte Flower Power";
          $options['message'] = $flowerpower['air_temperature']['instruction_key'];
          $cmdalerte->execCmd($options);
        }
      } else {
        $cmdlogic->setConfiguration('alert', '0');
      }
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['air_temperature']['status_key']);
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'air_temperature_instruction');
      $cmdlogic->setConfiguration('value', $flowerpower['air_temperature']['instruction_key']);
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['air_temperature']['instruction_key']);
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'soil_moisture');
      $cmdlogic->setConfiguration('value', round($flowerpower['watering']['soil_moisture']['gauge_values']['current_value'],2));
      $cmdlogic->save();
      $cmdlogic->event(round($flowerpower['watering']['soil_moisture']['gauge_values']['current_value'],2));
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'soil_moisture_status');
      $cmdlogic->setConfiguration('value', $flowerpower['watering']['soil_moisture']['status_key']);
      if ($flowerpower['watering']['soil_moisture']['status_key'] != 'status_ok') {
        if ($cmdlogic->getConfiguration('alert') == '0' && $alert != '') {
          $cmdlogic->setConfiguration('alert', '1');
          $cmdalerte = cmd::byId($alert);
          $options['title'] = "Alerte Flower Power";
          $options['message'] = $flowerpower['watering']['soil_moisture']['instruction_key'];
          $cmdalerte->execCmd($options);
        }
      } else {
        $cmdlogic->setConfiguration('alert', '0');
      }
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['watering']['soil_moisture']['status_key']);
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'soil_moisture_instruction');
      $cmdlogic->setConfiguration('value', $flowerpower['watering']['soil_moisture']['instruction_key']);
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['watering']['soil_moisture']['instruction_key']);
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'fertilizer');
      $cmdlogic->setConfiguration('value', round($flowerpower['fertilizer']['gauge_values']['current_value'],2));
      $cmdlogic->save();
      $cmdlogic->event(round($flowerpower['fertilizer']['gauge_values']['current_value'],2));
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'fertilizer_status');
      $cmdlogic->setConfiguration('value', $flowerpower['fertilizer']['status_key']);
      if ($flowerpower['fertilizer']['status_key'] != 'status_ok') {
        if ($cmdlogic->getConfiguration('alert') == '0' && $alert != '') {
          $cmdlogic->setConfiguration('alert', '1');
          $cmdalerte = cmd::byId($alert);
          $options['title'] = "Alerte Flower Power";
          $options['message'] = $flowerpower['fertilizer']['instruction_key'];
          $cmdalerte->execCmd($options);
        }
      } else {
        $cmdlogic->setConfiguration('alert', '0');
      }
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['fertilizer']['status_key']);
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'fertilizer_instruction');
      $cmdlogic->setConfiguration('value', $flowerpower['fertilizer']['instruction_key']);
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['fertilizer']['instruction_key']);
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'light');
      $cmdlogic->setConfiguration('value', round($flowerpower['light']['gauge_values']['current_value'],2));
      $cmdlogic->save();
      $cmdlogic->event(round($flowerpower['light']['gauge_values']['current_value'],2));
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'light_status');
      $cmdlogic->setConfiguration('value', $flowerpower['light']['status_key']);
      if ($flowerpower['light']['status_key'] != 'status_ok') {
        if ($cmdlogic->getConfiguration('alert') == '0' && $alert != '') {
          $cmdlogic->setConfiguration('alert', '1');
          $cmdalerte = cmd::byId($alert);
          $options['title'] = "Alerte Flower Power";
          $options['message'] = $flowerpower['light']['instruction_key'];
          $cmdalerte->execCmd($options);
        }
      } else {
        $cmdlogic->setConfiguration('alert', '0');
      }
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['light']['status_key']);
      $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'light_instruction');
      $cmdlogic->setConfiguration('value', $flowerpower['light']['instruction_key']);
      $cmdlogic->save();
      $cmdlogic->event($flowerpower['light']['instruction_key']);
      $flowerpowerbt->batteryStatus($flowerpower['battery']['gauge_values']['current_value']);
      $flowerpowerbt->setConfiguration('batteryStatus',$flowerpower['battery']['gauge_values']['current_value']);
      $flowerpowerbt->save();
      $flowerpowerbt->refreshWidget();
    }
  }

  public function toHtml($_version = 'dashboard') {
    $replace = $this->preToHtml($_version);
    if (!is_array($replace)) {
      return $replace;
    }
    $version = jeedom::versionAlias($_version);
    if ($this->getDisplay('hideOn' . $version) == 1) {
      return '';
    }

    foreach ($this->getCmd('info') as $cmd) {
      $replace['#' . $cmd->getLogicalId() . '_history#'] = '';
      $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
      $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
      $replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
      if ($cmd->getIsHistorized() == 1) {
        $replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
      }
    }
    $replace['#avatar#'] = $this->getConfiguration('avatar_url');
    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'flowerpowerbt', 'flowerpowerbt')));
  }

}

class flowerpowerbtCmd extends cmd {
  public function execute($_options = null) {
    return $this->getConfiguration('value');
  }
}
