const net = require('net');
const {spawn} = require('child_process');

process.env.ASTAGIDIR = '/var/lib/asterisk/agi-bin';

//agi://127.0.0.1/dialparties.agi

process.chdir(process.env.ASTAGIDIR);

const server = net.createServer((sock) => {
	let child = null;
	let exited = false;
	let init = false;
	let initCount = 0
	let settings = {};
	let initBuffer = [];
	let args = [];
	let threadid = sock.remotePort;
	console.log(`[${threadid}] Asterisk connection opened`);

	sock.on('data', (data) => {
		var utf8 = data.toString('utf8');
		if(!init) {
			console.log(`[${threadid}] Gathering settings...`);
			initBuffer.push(utf8);

			utf8.split("\n").forEach(setting => {
				if(!setting.trim().length) {
					return;
				}
				let s = setting.split(":").map((item) => {
					return item.trim();
				});
				settings[s[0]] = s[1];
			});

			if(initCount > 2) {
				console.log(initBuffer);
			}

			initCount++;

			if(data.toString('utf8').slice(-2) === '\n\n') {
				console.log(`[${threadid}] Finished gathering settings`);
				console.log(settings);
				let i = 1;
				while(typeof settings['agi_arg_'+i] !== "undefined") {
					args.push(settings['agi_arg_'+i]);
					i++;
				}
				console.log(`[${threadid}] Launching ${settings.agi_network_script} with args ${args.join(',')}`);
				child = spawn(`./${settings.agi_network_script}`, args, {cwd: process.env.ASTAGIDIR});
				child.stdout.on('data', function(data){
					console.log(`[${threadid}][${settings.agi_uniqueid}] <<< ${data.toString('utf8').trim()}`);
					sock.write(data.toString('utf8'));
				});

				child.on('exit',(code, signal) => {
					console.log(`[${threadid}] Script ended with code ${code}`);
					sock.end();
					exited = true;
				});
				init = true;
				initBuffer.forEach((b) => {
					child.stdin.write(b);
				});
			}
		} else if(!exited) {
			console.log(`[${threadid}][${settings.agi_uniqueid}] >>> ${data.toString('utf8').trim()}`);
			child.stdin.write(data.toString('utf8'));
		} else {
			console.log(`[${threadid}][${settings.agi_uniqueid}] >>> ${data.toString('utf8').trim()} !!! Exited`);
		}
	});

	sock.on('close', () => {
		console.log(`[${threadid}] Asterisk connection closed`);
	});
	sock.on('error', (err) => {
		console.log(`[${threadid}] Asterisk connection error: ${err.message}`);
	});
});

server.listen(4573, 'localhost');