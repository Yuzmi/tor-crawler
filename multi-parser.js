var cmd = require("node-cmd");

var urls = [
	/*"http://truegold2fbxij75.onion/", 
	"http://3xf422xsvz27xb6r.onion/", 
	"http://mega3j3v6lxlyko5.onion/"*/
];

var countParallel = 4;
var countPerRequest = 100;
var iRequest = 0;

function getUrls(callback) {
	var command = "php bin/console app:get:urls "+countPerRequest+" "+(countPerRequest*iRequest);
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

function getContents(i) {
	for(var a=0;a<countParallel;a++) {
		getNextContent(i);
	}
}

function getNextContent(i) {
	if(iRequest == i) {
		if(urls.length > 0) {
			var url = urls.shift();
			getContent(url, function() {
				getNextContent(i);
			});
		} else {
			iRequest++;
			getUrls(function() {
				getContents(iRequest);
			});
		}
	}
}

function getContent(url, callback) {
	var command = "php bin/console app:parse "+url;
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

getUrls(function() {
	getContents(iRequest);
});
