var cmd = require("node-cmd");

var urls = ["http://tt3j2x4kzdwaaa46.onion/", "http://dceifbk6qw55xmxd.onion/", "http://z52pujymseieakyk.onion/"];

var countPerRequest = 1000;
var iRequest = 0;

function getUrls(callback) {
	var command = "php bin/console app:get:urls "+countPerRequest;
	cmd.get(command, function(err, data, stderr) {
		if (!err) {
			urls = JSON.parse(data);
        } else {
        	console.log(err);
        }

        iRequest++;

        if(callback) callback();
	});
}

function parseUrls(i) {
	parseNextUrl(i);
	//parseNextUrl(i);
	//parseNextUrl(i);
}

function parseNextUrl(i) {
	if(iRequest == i) {
		if(urls.length > 0) {
			var url = urls.shift();
			parseUrl(url, parseNextUrl(i));
		} else {
			getUrls(parseUrls);
		}
	}
}

function parseUrl(url, callback) {
	var command = "php bin/console app:parse:url "+url;
	cmd.get(command, function(err, data, stderr) {
		if (!err) {
           console.log(rmNewline(data));
        } else {
           console.log(err);
        }

        if(callback) callback();
	});
}

function rmNewline(str) {
	return str.replace(/\r?\n|\r/g, " ");
}

//parseUrl("http://hwikis25ilfucqzh.onion/");

/*getUrls(function() {
	parseUrls(iRequest);
});*/
parseUrls(iRequest);

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