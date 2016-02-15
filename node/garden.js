var FlowerPowerCloud = require('flower-power-api');
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

var api = new FlowerPowerCloud();

var credentials = {
	'username'		: username,
	'password'		: password,
	'client_id'		: client_id,
	'client_secret'	: client_secret,
	'auto-refresh'	: false
};

api.login(credentials, function(err, res) {
	if (err) console.log(err.toString());
	else {
		api.getGarden(function(err, res) {
			console.log(res);
      url = urlJeedom + "&messagetype=saveGarden&type=flowerpowerbt";
    	request({
    		url: url,
    		method: 'PUT',
    		json: res,
    	},
    function (error, response, body) {
    	  if (!error && response.statusCode == 200) {
    		console.log("Got response Value: " + response.statusCode);
    	  }else{
    	  	console.log("SaveValue Error : "  + error );
    	  }
    	});
		});
	}
});
