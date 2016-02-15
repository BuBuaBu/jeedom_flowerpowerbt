var CloudAPI = require('./flower-power-cloud');
var async = require('async');
var request = require('request');
var urlJeedom = '';
var username = '';
var password = '';
var client_id = '';
var client_secret = '';

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

// print process.argv
process.argv.forEach(function(val, index, array) {
	switch ( index ) {
    case 2 : urlJeedom = val; break;
		case 3 : username = val; break;
		case 4 : password = val; break;
		case 5 : client_id = val; break;
		case 6 : client_secret = val; break;
	}

});

var clientID     = client_id
  , clientSecret = client_secret
  , userName     = username
  , passPhrase   = password
  , api
  ;


api = new CloudAPI.CloudAPI({ clientID: clientID, clientSecret: clientSecret }).login(userName, passPhrase, function(err) {
  if (!!err) return console.log('login error: ' + err.message);

  api.getGarden(function(err, plants, sensors) {
    if (!!err) return console.log('getGarden: ' + err.message);

    console.log('plants:'); console.log(plants);
    console.log('sensors:'); console.log(sensors);
  });
}).on('error', function(err) {
  console.log('background error: ' + err.message);
});
