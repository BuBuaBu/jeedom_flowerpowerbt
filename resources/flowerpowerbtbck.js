var async = require('async');
var FlowerPower = require('./index');
var hasCalibratedData = false;
var request = require('request');
var urlJeedom = '';

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

// print process.argv
process.argv.forEach(function(val, index, array) {

	switch ( index ) {
		case 2 : urlJeedom = val; break;
	}

});

function publish (value) {
  url = urlJeedom + '&type=flowerpowerbt&messagetype=saveFlower' + value;
	console.log(url);
    request(url, function (error, response, body) {
	  if (!error && response.statusCode != 200) {
		LogDate("error", "Got response : " + response.statusCode);
	  }
	});
}

FlowerPower.discoverAll(function(flowerPower) {
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
      flowerPower.readSystemId(function(error, systemId) {
        global.systemId = systemId;
        callback();
      });
    },
    function(callback) {
      flowerPower.readSerialNumber(function(error, serialNumber) {
        global.serialNumber = serialNumber;
        callback();
      });
    },
    function(callback) {
      flowerPower.readFirmwareRevision(function(error, firmwareRevision) {
        var version = firmwareRevision.split('_')[1].split('-')[1];
        hasCalibratedData = (version >= '1.1.0');
        callback();
      });
    },
    function(callback) {
      flowerPower.readBatteryLevel(function(error, batteryLevel) {
        global.batteryLevel = batteryLevel;
        callback();
      });
    },
    function(callback) {
      flowerPower.readColor(function(error, color) {
        global.color = color;
        callback();
      });
    },
    function(callback) {
      flowerPower.readSunlight(function(error, sunlight) {
        global.sunlight = sunlight.toFixed(2); //mol/m²/d'
        callback();
      });
    },
    function(callback) {
      flowerPower.readSoilTemperature(function(error, temperature) {
        global.soilTemperature = temperature.toFixed(2); //'°C');
        callback();
      });
    },
    function(callback) {
      flowerPower.readAirTemperature(function(error, temperature) {
        global.airTemperature = temperature.toFixed(2);
        callback();
      });
    },
    function(callback) {
      flowerPower.readSoilMoisture(function(error, soilMoisture) {
        global.soilMoisture = soilMoisture.toFixed(2);
        callback();
      });
    },
    function(callback) {
      if (hasCalibratedData) {
        async.series([
          function(callback) {
            flowerPower.readCalibratedSoilMoisture(function(error, soilMoisture) {
              global.soilMoisture = soilMoisture.toFixed(2);
              callback();
            });
          },
          function(callback) {
            flowerPower.readCalibratedAirTemperature(function(error, temperature) {
              global.airTemperature = temperature.toFixed(2);
              callback();
            });
          },
          function(callback) {
            flowerPower.readCalibratedSunlight(function(error, sunlight) {
              global.sunlight = sunlight.toFixed(2); //mol/m²/d'
              callback();
            });
          },
          function(callback) {
            flowerPower.readCalibratedEa(function(error, ea) {
              global.ea = ea.toFixed(2); //mol/m²/d'
              callback();
            });
          },
          function(callback) {
            flowerPower.readCalibratedEcb(function(error, ecb) {
              global.ecb = ecb.toFixed(2); //mol/m²/d'
              callback();
            });
          },
          function(callback) {
            flowerPower.readCalibratedEcPorous(function(error, ecPorous) {
              global.ecPorous = ecPorous.toFixed(2); //mol/m²/d'
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
    },
    function(callback) {
      var result = '&systemId=' + global.systemId + '&batteryLevel=' + global.batteryLevel + '&color=' + global.color + '&sunlight=' + global.sunlight + '&soilTemperature=' + global.soilTemperature + '&airTemperature' + global.airTemperature + '&soilMoisture=' + global.soilMoisture + '&ea=' + global.ea + '&ecb=' + global.ecb + '&ecPorous=' + global.ecPorous;
			publish(result);

      flowerPower.disconnect(callback);
    }
  ]);
});
