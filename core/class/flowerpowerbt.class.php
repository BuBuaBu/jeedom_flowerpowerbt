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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
if (!class_exists('fpParrotAPI')) { require_once dirname(__FILE__) . '/../../3rdparty/flowerpower.php'; }

class flowerpowerbt extends eqLogic {

  public static $_widgetPossibility = array('custom' => true);

  public static function cronHourly() {
    flowerpowerbt::getFlower();
    log::add('flowerpowerbt', 'debug', 'Récupération valeurs');
  }

  public static function cronDaily() {
    config::save('refresh_token', '',  'flowerpowerbt');
    flowerpowerbt::getGarden();
    flowerpowerbt::deamon_stop();
  }

  public static function dependancy_info() {
    $return = array();
    $return['log'] = 'flowerpowerbt_dep';
    $flowerapi = realpath(dirname(__FILE__) . '/../../node/node_modules/flower-power-api');
    $flowerble = realpath(dirname(__FILE__) . '/../../node/node_modules/flower-power-ble');
    $return['progress_file'] = '/tmp/flowerpowerbt_dep';
    if (is_dir($flowerble) && is_dir($flowerapi)) {
      $return['state'] = 'ok';
    } else {
      $return['state'] = 'nok';
    }
    return $return;
  }

  public static function dependancy_install() {
    log::add('flowerpowerbt','info','Installation des dépendances nodejs');
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('flowerpowerbt_dep') . ' 2>&1 &');
    flowerpowerbt::doConf();
  }

  public static function deamon_info() {
    $return = array();
    $return['log'] = 'flowerpowerbt_node';
    $return['state'] = 'nok';
    $pid = trim( shell_exec ('ps ax | grep "flowerpowerbt/node/start.js" | grep -v "grep" | wc -l') );
    if ($pid != '' && $pid != '0') {
      $return['state'] = 'ok';
    }
    $return['launchable'] = 'ok';
    if (config::byKey('cloudActive', 'flowerpowerbt') != '1') {
      $return['launchable'] = 'nok';
      $return['launchable_message'] = __('Synchro Parrot non active', __FILE__);
    }
    return $return;
  }

  public static function deamon_start($_debug = false) {
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    log::add('flowerpowerbt', 'info', 'Lancement du démon flowerpowerbt');
    $sensor_path = realpath(dirname(__FILE__) . '/../../node');
    $cmd = 'nodejs ' . $sensor_path . '/start.js 60 ';
    $port = str_replace('hci', '', jeedom::getBluetoothMapping(config::byKey('port', 'flowerpowerbt',0)));
    $cmd = 'NOBLE_HCI_DEVICE_ID=' . $port . ' ' . $cmd;

    log::add('flowerpowerbt', 'debug', 'Lancement démon flowerpowerbt : ' . $cmd);

    $result = exec('nohup sudo ' . $cmd . ' >> ' . log::getPathToLog('flowerpowerbt_node') . ' 2>&1 &');
    if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
      log::add('flowerpowerbt', 'error', $result);
      return false;
    }

    $i = 0;
    while ($i < 30) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] == 'ok') {
        break;
      }
      sleep(1);
      $i++;
    }
    if ($i >= 30) {
      log::add('flowerpowerbt', 'error', 'Impossible de lancer le démon flowerpowerbt, vérifiez le port', 'unableStartDeamon');
      return false;
    }
    message::removeAll('flowerpowerbt', 'unableStartDeamon');
    log::add('flowerpowerbt', 'info', 'Démon flowerpowerbt lancé');
    return true;
  }

  public static function deamon_stop() {
    exec('kill $(ps aux | grep "flowerpowerbt/node/start.js" | awk \'{print $2}\')');
    log::add('flowerpowerbt', 'info', 'Arrêt du service flowerpowerbt');
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      sleep(1);
      exec('kill -9 $(ps aux | grep "flowerpowerbt/node/start.js" | awk \'{print $2}\')');
    }
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      sleep(1);
      exec('sudo kill -9 $(ps aux | grep "flowerpowerbt/node/start.js" | awk \'{print $2}\')');
    }
    config::save('gateway', '0',  'flowerpowerbt');
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
      "password": "' . $passPhrase. '"
    }';
    file_put_contents($sensor_path, $content);
    /*$content1 = '{';
    $content2 = '      	"client_id": "' . $clientID . '",';
    $content3 = '      	"client_secret": "' . $clientSecret. '",';
    $content4 = '      	"username": "' . $userName. '",';
    $content5 = '      	"password": "' . $passPhrase. '"';
    $content6 = '}';
    file_put_contents($sensor_path, $content1 . "\n", LOCK_EX);
    file_put_contents($sensor_path, $content2 . "\n", FILE_APPEND | LOCK_EX);
    file_put_contents($sensor_path, $content3 . "\n", FILE_APPEND | LOCK_EX);
    file_put_contents($sensor_path, $content4 . "\n", FILE_APPEND | LOCK_EX);
    file_put_contents($sensor_path, $content5 . "\n", FILE_APPEND | LOCK_EX);
    file_put_contents($sensor_path, $content6, FILE_APPEND | LOCK_EX);*/
  }

  public static function getGarden() {
    if (config::byKey('jeeNetwork::mode') == 'master') {
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
        $flowerpowerbt = self::byLogicalId($device->location_identifier, 'flowerpowerbt');
        if (!is_object($flowerpowerbt)) {
          $flowerpowerbt = new flowerpowerbt();
          $flowerpowerbt->setEqType_name('flowerpowerbt');
          $flowerpowerbt->setLogicalId($device->location_identifier);
          $flowerpowerbt->setName('Flower - '. $device->location_identifier);
          $flowerpowerbt->setConfiguration('sensor_serial',$device->sensor_serial);
          $flowerpowerbt->setConfiguration('location_identifier',$device->location_identifier);
          $flowerpowerbt->setConfiguration('battery_type','1x AAA');
          $flowerpowerbt->setIsEnable(true);
          $flowerpowerbt->save();
        }
        if (strpos($device->avatar_url,'http') === false) {
          $module=json_encode($device);
          $sensor=json_decode($module, true);
          $avatar_url = $sensor['images'][0]['url'];
        } else {
          $avatar_url = $device->avatar_url;
        }
        $flowerpowerbt->setConfiguration('plant_nickname',$device->plant_nickname);
        $flowerpowerbt->setConfiguration('avatar_url',$avatar_url);
        $flowerpowerbt->save();

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
          $cmdlogic->setSubType('other');
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
          $cmdlogic->setSubType('other');
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
          $cmdlogic->setSubType('other');
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
          $cmdlogic->setSubType('other');
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
          $cmdlogic->setSubType('other');
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
          $cmdlogic->setSubType('other');
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
          $cmdlogic->setSubType('other');
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
          $cmdlogic->setSubType('other');
          $cmdlogic->save();
        }
      }

      $plants=$flowerpower->getSensors();
      //log::add('flowerpowerbt', 'debug', 'Garden ' . print_r($plants,true));

      foreach ($plants as $device) {
        foreach (eqLogic::byType('flowerpowerbt', true) as $flowerpowerbt) {
          if ($flowerpowerbt->getConfiguration('sensor_serial') == $device->sensor_serial) {
            $flowerpowerbt->setConfiguration('nickname',$device->nickname);
            if ($device->color == '6') {
              $color = 'vert';
            } else {
              $color = $device->color;
            }
            $flowerpowerbt->setConfiguration('color',$color);
            $flowerpowerbt->save();
          }
        }
      }

    }

  }


  public static function getFlower() {
    if (config::byKey('jeeNetwork::mode') == 'master') {
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

      $sensors=$flowerpower->getSensorsValues();
      //log::add('flowerpowerbt', 'debug', 'SensorValues ' . print_r($sensors,true));

      foreach ($values as $mesure) {
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
        $cmdlogic->setConfiguration('value', round($flowerpower['soil_moisture']['gauge_values']['current_value'],2));
        $cmdlogic->save();
        $cmdlogic->event(round($flowerpower['soil_moisture']['gauge_values']['current_value'],2));
        $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'soil_moisture_status');
        $cmdlogic->setConfiguration('value', $flowerpower['soil_moisture']['status_key']);
        if ($flowerpower['soil_moisture']['status_key'] != 'status_ok') {
          if ($cmdlogic->getConfiguration('alert') == '0' && $alert != '') {
            $cmdlogic->setConfiguration('alert', '1');
            $cmdalerte = cmd::byId($alert);
            $options['title'] = "Alerte Flower Power";
            $options['message'] = $flowerpower['soil_moisture']['instruction_key'];
            $cmdalerte->execCmd($options);
          }
        } else {
          $cmdlogic->setConfiguration('alert', '0');
        }
        $cmdlogic->save();
        $cmdlogic->event($flowerpower['soil_moisture']['status_key']);
        $cmdlogic = flowerpowerbtCmd::byEqLogicIdAndLogicalId($id,'soil_moisture_instruction');
        $cmdlogic->setConfiguration('value', $flowerpower['soil_moisture']['instruction_key']);
        $cmdlogic->save();
        $cmdlogic->event($flowerpower['soil_moisture']['instruction_key']);
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

      }

      foreach ($sensors as $mesure) {
        $module=json_encode($mesure);
        $flowerpower=json_decode($module, true);
        foreach (eqLogic::byType('flowerpowerbt', true) as $flowerpowerbt) {
          if ($flowerpowerbt->getConfiguration('sensor_serial') == $flowerpower['sensor_serial']) {
            $flowerpowerbt->batteryStatus($flowerpower['battery_level']['level_percent']);
            $flowerpowerbt->save();
            $flowerpowerbt->refreshWidget();
          }
        }
      }

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

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'current', 'flowerpowerbt')));
  }

}

class flowerpowerbtCmd extends cmd {
  public function execute($_options = null) {
    return $this->getConfiguration('value');
    log::add('flowerpowerbt', 'info', 'Commande recue ' . $this->getConfiguration('value'));
  }
}
