var Agent = require('socks5-http-client/lib/Agent');
var cmd = require("node-cmd");
var crypto = require("crypto");
var fs = require('fs');
var request = require('request');

require('events').EventEmitter.prototype._maxListeners = 100;

var chunkSize = 500;
var counter = 0;
var countThreads = 10;
var filter = null;
var firstChunkOnly = false;
var iChunk = 0;
var iLoop = 0;
var iSession = 0;
var iThread = 0;
var loop = false;
var order = null;
var urls = [];
var timeout = 60000;

// Arguments
var argv = require('minimist')(process.argv.slice(2), { boolean: true });
for(var arg in argv) {
	if(arg == "f" || arg == "filter") {
		filter = argv[arg];
	} else if(arg == "first-only") {
		firstChunkOnly = true;
	} else if((arg == "l" && argv[arg] == true) || (arg == "loop" && argv[arg] == true)) {
		loop = true;
	} else if(arg == "o" || arg == "order") {
		order = argv[arg];
	} else if(arg == "s" || arg == "threads") {
		countThreads = argv[arg];
	} else if(arg == "t" || arg == "timeout") {
		timeout = argv[arg] * 1000;
	}
}

var gettingUrls = false;
function getUrls(callback) {
	if(gettingUrls) {
		return;
	} else {
		gettingUrls = true;
	}

	var command = "php "+__dirname+"/bin/console app:get:urls "+chunkSize+" "+(chunkSize*iChunk);
	if(filter) command += " -f "+filter;
	if(order) command += " -o "+order;

	cmd.get(command, function(err, data, stderr) {
		gettingUrls = false;
		if (!err) {
			urls = JSON.parse(data);
			if(urls.length > 0) {
				if(callback) callback();
			} else if(loop && iChunk > 0) {
				iChunk = 0;
				iLoop++;
				getUrls(function() {
					if(callback) callback();
				});
			}
        } else {
        	console.log(err);
        }
	});
}

function getUrlContents(iS) {
	if(iLoop == 0 || !firstChunkOnly) {
		for(var j=0;j<countThreads;j++) {
			setTimeout(function() {
				iThread++;
				getNextUrlContent(iS, iThread);
			}, j*200);
		}
	}
}

function getNextUrlContent(iS, iT) {
	if(iSession == iS) {
		if(urls.length > 0) {
			var url = urls.shift();
			getUrlContent(iT, url, function() {
				getNextUrlContent(iS, iT);
			});
		} else {
			iSession++;
			if(!firstChunkOnly) {
				iChunk++;
			}

			getUrls(function() {
				getUrlContents(iSession);
			});
		}
	}
}

function getUrlContent(iT, url, callback) {
	var isTimedOut = false;
	var timeStart = Date.now();

	// Result initialization
	var result = {
		"success": false,
		"url": url,
		"content": null,
		"error": null,
		"dateUTC": new Date().toUTCString(),
		"duration": 0,
		"httpCode": null,
		"contentType": null
	};

	var t = null;

	// Request
	var r = request({
		url: url,
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

		if(res) {
			result.httpCode = res.statusCode;
			result.contentType = res.headers["content-type"];
		}

		saveUrlResult(iT, url, result);

		if(callback) callback();
		return;
	});

	// Timeout
	t = setTimeout(function() {
		r.abort();

		result.error = "Timeout";
		result.duration = Math.round((Date.now() - timeStart) / 1000);

		saveUrlResult(iT, url, result);

		if(callback) callback();
		return;
	}, timeout);
}

function saveUrlResult(iT, url, result) {
	var filename = hash(url)+".json";
	fs.writeFile(__dirname+"/var/files/"+filename, JSON.stringify(result), function(errFs) {
		if(errFs) {
			console.log(errFs);
		} else {
			parseFile(filename);
		}
	});

	counter++;
	if(!result.error) {
		console.log("#"+counter+(iLoop > 0 ? "/"+(iLoop + 1) : "")+" - T"+(iT)+" - "+url+" : "+result.duration+"s - OK");
	} else {
		console.log("#"+counter+(iLoop > 0 ? "/"+(iLoop + 1) : "")+" - T"+(iT)+" - "+url+" : "+result.duration+"s - "+result.error);
	}
}

function hash(data) {
	return crypto.createHash("sha1").update(data).digest("hex");
}

function parseFile(file, callback) {
	var command = "php "+__dirname+"/bin/console app:parse:files "+__dirname+"/var/files/"+file;
	cmd.get(command, function(err, data, stderr) {
		if(err) {
			console.log(err);
		}

		if(callback) {
			callback();
		}
	});
}

getUrls(function() {
	getUrlContents(iSession);
});
