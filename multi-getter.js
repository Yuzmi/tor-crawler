var Agent = require('socks5-http-client/lib/Agent');
var cmd = require("node-cmd");
var fs = require('fs');
var request = require('request');

require('events').EventEmitter.prototype._maxListeners = 100;

var counter = 0;
var countPerChunk = 200;
var countThreads = 10;
var iChunk = 0;
var loop = false;
var looped = 0;
var onions = [];
var timeout = 30000;

// Arguments
var argv = require('minimist')(process.argv.slice(2), { boolean: true });
for(var arg in argv) {
	if((arg == "l" && argv[arg] == true) || (arg == "loop" && argv[arg] == true)) {
		loop = true;
	} else if(arg == "s" || arg == "threads") {
		countThreads = argv[arg];
	} else if(arg == "t" || arg == "timeout") {
		timeout = argv[arg] * 1000; // Received in seconds
	}
}

function getOnions(callback) {
	var command = "php bin/console app:get:onions "+countPerChunk+" "+(countPerChunk*iChunk);
	cmd.get(command, function(err, data, stderr) {
		if (!err) {
			onions = JSON.parse(data);
			if(onions.length > 0) {
				if(callback) callback();
			} else if(loop) {
				iChunk = 0;
				looped++;
				getOnions(function() {
					if(callback) callback();
				});
			}
        } else {
        	console.log(err);
        }
	});
}

function getOnionContents(iC) {
	for(var j=0;j<countThreads;j++) {
		setTimeout(function(j) {
			getNextOnionContent(iC, j);
		}, j*500, j);
	}
}

function getNextOnionContent(iC, iT) {
	if(iChunk == iC) {
		if(onions.length > 0) {
			var onion = onions.shift();
			getOnionContent(iC, iT, onion, function() {
				getNextOnionContent(iC, iT);
			});
		} else {
			iChunk++;
			getOnions(function() {
				getOnionContents(iChunk);

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

function getOnionContent(iC, iT, onion, callback) {
	var isTimedOut = false;
	var timeStart = Date.now();

	// Result initialization
	var result = {
		"success": false,
		"onion": onion,
		"content": null,
		"error": null,
		"date": new Date().toISOString(),
		"duration": 0
	};

	var t = null;

	// Request
	var r = request({
		url: "http://"+onion+".onion",
		agentClass: Agent,
		agentOptions: {
			socksHost: "localhost",
			socksPort: 9050
		}
	}, function(errReq, res, body) {
		clearTimeout(t);

		result.success = !errReq ? true : false;
		result.content = !errReq ? body : null;
		result.error = errReq ? errReq.toString() : null;
		result.duration = Math.round((Date.now() - timeStart) / 1000);

		saveOnionResult(iC, iT, onion, result);

		if(callback) callback();
		return;
	});

	// Timeout
	t = setTimeout(function() {
		r.abort();

		result.error = "Timeout";
		result.duration = Math.round((Date.now() - timeStart) / 1000);

		saveOnionResult(iC, iT, onion, result);

		if(callback) callback();
		return;
	}, timeout);
}

function saveOnionResult(iC, iT, onion, result) {
	fs.writeFile("var/onions/"+onion+".json", JSON.stringify(result), function(errFs) {
		if(errFs) {
			console.log(errFs);
		}
	});

	counter++;
	if(!result.error) {
		console.log(counter+(looped > 0 ? "/"+(looped + 1) : "")+" - "+(iC + 1)+"-"+(iT + 1)+" - "+onion+" : "+result.duration+"s - OK");
	} else {
		console.log(counter+(looped > 0 ? "/"+(looped + 1) : "")+" - "+(iC + 1)+"-"+(iT + 1)+" - "+onion+" : "+result.duration+"s - "+result.error);
	}
}

getOnions(function() {
	getOnionContents(iChunk);
});
