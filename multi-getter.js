var Agent = require('socks5-http-client/lib/Agent');
var cmd = require("node-cmd");
var fs = require('fs');
var request = require('request');

var countParallel = 10;
var countPerRequest = 100;
var iRequest = 0;
var iParsed = 0;
var onions = [];

function getOnions(callback) {
	var command = "php bin/console app:get:onions "+countPerRequest+" "+(countPerRequest*iRequest)+" -s";
	cmd.get(command, function(err, data, stderr) {
		if (!err) {
			onions = JSON.parse(data);
        } else {
        	console.log(err);
        }

        iRequest++;

        if(callback) callback();
	});
}

function getOnionContents(i) {
	for(var a=0;a<countParallel;a++) {
		getNextOnionContent(i);
	}
}

function getNextOnionContent(i) {
	if(iRequest == i) {
		if(onions.length > 0) {
			var onion = onions.shift();
			getOnionContent(onion, function() {
				getNextOnionContent(i);
			});
		} else {
			iRequest++;
			getOnions(function() {
				getOnionContents(iRequest);

				var command = "php bin/console app:parse:files";
				cmd.get(command, function(err, data, stderr) {
					if(err) {
						console.log(err);
					}
				});
			});
		}
	}
}

function getOnionContent(onion, callback) {
	var timeStart = Date.now() / 1000;
	request({
		url: "http://"+onion+".onion",
		agentClass: Agent,
		agentOptions: {
			socksHost: "localhost",
			socksPort: 9050
		},
		timeout: 20000
	}, function(errReq, res) {
		var timeEnd = Date.now() / 1000;
		var duration = (timeEnd - timeStart).toFixed(3);

		var onionFile = {
			"success": !errReq ? true : false,
			"onion": onion,
			"content": !errReq ? res.body : null,
			"error": errReq ? errReq.toString() : null,
			"date": new Date().toISOString(),
			"duration": duration
		};
		fs.writeFile("var/onions/"+onion+".json", JSON.stringify(onionFile), function(errFs) {
			if(errFs) {
				console.log(errFs);
			}
		});

		iParsed++;
		if(!errReq) {
			console.log(iParsed+" - "+onion+" : "+duration+"s : OK");
		} else {
			console.log(iParsed+" - "+onion+" : "+duration+"s : "+errReq);
		}

		if(callback) callback();
	});
}

getOnions(function() {
	getOnionContents(iRequest);
});

/*
// https://github.com/mattcg/socks5-http-client

var url = require('url');
var shttp = require('socks5-http-client');

var options = url.parse(process.argv[2]);

options.socksPort = 9050; // Tor default port.

var req = shttp.get(options, function(res) {
	res.setEncoding('utf8');

	res.on('readable', function() {
		var data = res.read();

		// Check for the end of stream signal.
		if (null === data) {
			process.stdout.write('\n');
			return;
		}

		process.stdout.write(data);
	});
});

req.on('error', function(e) {
	console.error('Problem with request: ' + e.message);
});

req.end();
*/