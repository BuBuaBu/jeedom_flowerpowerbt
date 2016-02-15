var async = require('async');

var FlowerPower = require('./index');

var hasCalibratedData = false;

var request = require('request');

var uuid = "undefined";

var urlJeedom = '';
var logLevel = new Array();

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

// print process.argv
process.argv.forEach(function(val, index, array) {

	switch ( index ) {
		case 2 : urlJeedom = val; break;
	}

});

function publish (info, sensor, value) {
		id = uuid;
  url = urlJeedom + "&messagetype=saveFlower&type=flowerpowerbt&sensor="+info+"&flower="+id+"&info=" + sensor + "&value="+value;

    //console.log(url);
    request(url, function (error, response, body) {
	  if (!error && response.statusCode != 200) {
		console.log(response.statusCode);
	  }
	});
}

FlowerPower.discover(function(flowerPower) {
  async.series([
    function(callback) {
      flowerPower.on('disconnect', function() {
        console.log('disconnected!');
        process.exit(0);
      });

      console.log('connectAndSetup');
      flowerPower.connectAndSetup(callback);
    },
    function(callback) {
      //console.log('readSystemId');
      flowerPower.readSystemId(function(error, systemId) {
        //console.log('\tsystem id = ' + systemId);
        uuid = systemId;
        publish('attribut','SystemId', systemId);
        callback();
      });
    },
    function(callback) {
      //console.log('readFirmwareRevision');
      flowerPower.readFirmwareRevision(function(error, firmwareRevision) {
        //console.log('\tfirmware revision = ' + firmwareRevision);

        var version = firmwareRevision.split('_')[1].split('-')[1];
        hasCalibratedData = (version >= '1.1.0');

        callback();
      });
    },
    function(callback) {
      //console.log('readBatteryLevel');
      flowerPower.readBatteryLevel(function(error, batteryLevel) {
        //console.log('battery level = ' + batteryLevel);
        publish('attribut','batteryLevel', batteryLevel);
        callback();
      });
    },
    function(callback) {
      //console.log('readColor');
      flowerPower.readColor(function(error, color) {
        //console.log('\tcolor = ' + color);
        publish('attribut','color', color);
        callback();
      });
    },
    function(callback) {
      //console.log('readSunlight');
      flowerPower.readSunlight(function(error, sunlight) {
        //console.log('sunlight = ' + sunlight.toFixed(2) + ' mol/m²/d');
        publish('sensor','sunlight', sunlight.toFixed(2));
        callback();
      });
    },
    function(callback) {
      //console.log('readSoilTemperature');
      flowerPower.readSoilTemperature(function(error, temperature) {
        //console.log('soil temperature = ' + temperature.toFixed(2) + '°C');
        publish('sensor','soilTemperature', temperature.toFixed(2));
        callback();
      });
    },
    function(callback) {
      //console.log('readAirTemperature');
      flowerPower.readAirTemperature(function(error, temperature) {
        //console.log('air temperature = ' + temperature.toFixed(2) + '°C');
        publish('sensor','airTemperature', temperature.toFixed(2));
        callback();
      });
    },
    function(callback) {
      //console.log('readSoilMoisture');
      flowerPower.readSoilMoisture(function(error, soilMoisture) {
        //console.log('soil moisture = ' + soilMoisture.toFixed(2) + '%');
        publish('sensor','soilMoisture', soilMoisture.toFixed(2));
        callback();
      });
    },
    function(callback) {
      if (hasCalibratedData) {
        async.series([
          function(callback) {
            //console.log('readCalibratedSoilMoisture');
            flowerPower.readCalibratedSoilMoisture(function(error, soilMoisture) {
              //console.log('calibrated soil moisture = ' + soilMoisture.toFixed(2) + '%');
              publish('sensor','soilMoisture', soilMoisture.toFixed(2));
              callback();
            });
          },
          function(callback) {
            //console.log('readCalibratedAirTemperature');
            flowerPower.readCalibratedAirTemperature(function(error, temperature) {
              //console.log('calibrated air temperature = ' + temperature.toFixed(2) + '°C');
              publish('sensor','airTemperature', temperature.toFixed(2));
              callback();
            });
          },
          function(callback) {
            //console.log('readCalibratedSunlight');
            flowerPower.readCalibratedSunlight(function(error, sunlight) {
              //console.log('calibrated sunlight = ' + sunlight.toFixed(2) + ' mol/m²/d');
              publish('sensor','sunlight', sunlight.toFixed(2));
              callback();
            });
          },
          function(callback) {
            //console.log('readCalibratedEa');
            flowerPower.readCalibratedEa(function(error, ea) {
              //console.log('calibrated EA = ' + ea.toFixed(2));
              publish('sensor','ea', ea.toFixed(2));
              callback();
            });
          },
          function(callback) {
            //console.log('readCalibratedEcb');
            flowerPower.readCalibratedEcb(function(error, ecb) {
              //console.log('calibrated ECB = ' + ecb.toFixed(2) + ' dS/m');
              publish('sensor','ecb', ecb.toFixed(2));
              callback();
            });
          },
          function(callback) {
            //console.log('readCalibratedEcPorous');
            flowerPower.readCalibratedEcPorous(function(error, ecPorous) {
              //console.log('calibrated EC porous = ' + ecPorous.toFixed(2) + ' dS/m');
              publish('sensor','ecPorous', ecPorous.toFixed(2));
              callback();
            });
          },
          function() {
            callback();
          }
        ]);
      } else {
        callback();
      }
    }
  ]);
});
