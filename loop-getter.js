var Agent = require('socks5-http-client/lib/Agent');
var cmd = require("node-cmd");
var colors = require("colors");
var crypto = require("crypto");
var fs = require('fs');
var request = require('request');

require('events').EventEmitter.prototype._maxListeners = 100;

var app = {
	counter: 0,
	countThreads: 10,
	requestTimeout: 60000, // 1 minute
	scriptTimeout: 0, // Forever

	init: function() {
		app.initParams();
		app.urls.getMore(function() {
			app.getUrlContents();
		});

		// Script timeout
		if(app.scriptTimeout > 0) {
			setTimeout(function() {
				app.debug.log("Script timeout");
				process.exit();
			}, app.scriptTimeout);
		}
	},

	initParams: function() {
		var argv = require('minimist')(process.argv.slice(2), { boolean: true });
		for(var arg in argv) {
			if(arg == "d" || arg == "debug") {
				app.debug.enabled = argv[arg];
			} else if(arg == "s" || arg == "shuffle") {
				app.urls.shuffle = argv[arg];
			} else if(arg == "t" || arg == "threads") {
				app.countThreads = argv[arg];
			} else if(arg == "request-timeout") {
				app.requestTimeout = argv[arg] * 1000;
			} else if(arg == "script-timeout") {
				app.scriptTimeout = argv[arg] * 1000;
			}
		}
		app.debug.log("Arguments initialized");
	},

	debug: {
		enabled: false,
		log: function(message) {
			if(app.debug.enabled) {
				message = "DEBUG : "+message;
				console.log(message.gray);
			}
		}
	},

	urls: {
		chunkSize: 400,
		getting: false,
		list: [],
		shuffle: false,

		getOne: function(callback) {
			if(app.urls.list.length > 0) {
				var url = app.urls.list.shift();
				if(callback) callback(url);
			} else {
				if(app.urls.getting) {
					app.debug.log("Waiting for more urls");
					setTimeout(function() {
						app.urls.getOne(callback);
					}, 5000);
				} else {
					app.urls.getMore(function() {
						app.urls.getOne(callback);
					});
				}
			}
		},

		getMore: function(callback) {
			app.urls.getting = true;
			app.debug.log("Getting more urls");

			var command = "php "+__dirname+"/bin/console app:get:routine-urls "+app.urls.chunkSize+" --env=prod --no-debug";
			cmd.get(command, function(err, data, stderr) {
				if(!err) {
					var urls = JSON.parse(data);
					if(app.urls.shuffle) {
						urls = app.utils.shuffle(urls);
					}

					if(urls.length > 0) {
						app.urls.list = urls;
						if(app.urls.list.length > 0) {
							if(callback) callback();
						}
						app.urls.getting = false;
					} else {
						app.debug.log("No url, waiting to try again.");
						setTimeout(function() {
							app.urls.getMore(callback);
						}, 60000);
					}
		        } else {
		        	app.urls.getting = false;
		        	console.log(err);
		        }
			});
		}
	},

	getUrlContents: function() {
		app.debug.log("Starting threads");
		for(var i=0;i<app.countThreads;i++) {
			app.debug.log("New thread");
			setTimeout(function() {
				app.getNextUrlContent();
			}, i*200);
		}
	},

	getNextUrlContent: function() {
		app.urls.getOne(function(url) {
			app.getUrlContent(url, function() {
				app.getNextUrlContent();
			});
		});
	},

	getUrlContent: function(url, callback) {
		var timeStart = Date.now();

		app.debug.log("Request start for \""+url+"\"");

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
		}, function(err, res, body) {
			clearTimeout(t);

			app.debug.log("Request end for \""+url+"\"");

			result.success = !err ? true : false;
			result.content = !err ? body : null;
			result.error = err ? err.toString() : null;
			result.duration = Math.round((Date.now() - timeStart) / 1000);

			if(res) {
				result.httpCode = res.statusCode;
				result.contentType = res.headers["content-type"];
			}

			app.saveUrlResult(url, result);

			if(callback) callback();
			return;
		});

		// Timeout
		t = setTimeout(function() {
			app.debug.log("Timeout for \""+url+"\"");

			r.abort();

			result.error = "Timeout";
			result.duration = Math.round((Date.now() - timeStart) / 1000);

			app.saveUrlResult(url, result);

			if(callback) callback();
			return;
		}, app.requestTimeout);
	},

	saveUrlResult: function(url, result) {
		app.debug.log("Saving result for \""+url+"\"");

		var filename = app.utils.hash(url)+".json";
		fs.writeFile(__dirname+"/var/files/"+filename, JSON.stringify(result), function(err) {
			if(err) {
				console.log(err);
			} else {
				app.parseFile(filename, url);
			}
		});

		app.counter++;
		if(!result.error) {
			console.log("#"+app.counter+" - "+url+" : "+result.duration+"s - OK");
		} else {
			console.log("#"+app.counter+" - "+url+" : "+result.duration+"s - "+result.error);
		}
	},

	parseFile: function(file, url) {
		app.debug.log("Parsing file for \""+url+"\"");

		var command = "php "+__dirname+"/bin/console app:parse:files "+__dirname+"/var/files/"+file+" --env=prod --no-debug";
		cmd.get(command, function(err, data, stderr) {
			if(err) {
				console.log(err);
			}
		});
	},

	utils: {
		hash: function(data) {
			return crypto.createHash("sha1").update(data).digest("hex");
		},

		// https://stackoverflow.com/questions/6274339
		shuffle: function(a) {
			var j, x, i;
		    for (i = a.length - 1; i > 0; i--) {
		        j = Math.floor(Math.random() * (i + 1));
		        x = a[i];
		        a[i] = a[j];
		        a[j] = x;
		    }
		    return a;
		}
	}
};

app.init();
