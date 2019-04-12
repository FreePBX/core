const {spawn} = require('child_process');
const EventEmitter = require('events');
const path = require('path');

class AGI extends EventEmitter {
	constructor(settings) {
		super();
		this.args = [];
		this.settings = settings;
		this.agiScript = null;
		this.exited = false;

		let i = 1;
		while(typeof settings['agi_arg_'+i] !== "undefined") {
			this.args.push(settings['agi_arg_'+i]);
			i++;
		}

		this.launch();
	}

	kill() {
		this.agiScript.kill();
	}

	/**
	 * Launch AGI Script
	 */
	launch() {
		let scriptPath = `./${this.settings.agi_network_script}`;
		if(path.isAbsolute(this.settings.agi_network_script)) {
			scriptPath = this.settings.agi_network_script;
		}
		console.log(`[${this.settings.agi_port}] Launching ${scriptPath} with args: ${this.args.join(',')}`);

		this.agiScript = spawn(scriptPath, this.args, {cwd: this.settings.agi_ASTAGIDIR});
		this.agiScript.stdout.on('data', this.scriptStdout.bind(this));
		this.agiScript.on('exit', this.scriptExit.bind(this));

		this.agiScript.stdout.on('error', (error) => {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] <<< ${JSON.stringify(error)}`);
		});
		this.agiScript.stdin.on('error', (error) => {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>> ${JSON.stringify(error)}`);
		});

		//push settings to script
		for (var key in this.settings) {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>> ${key}: ${this.settings[key]}`);
			this.agiScript.stdin.write(key+": "+this.settings[key]+"\n");
		}
		console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>> `);
		//done pushing settings return two newlines to tell script settings are over
		this.agiScript.stdin.write("\n\n");
	}

	/**
	 * Called when the AGI Script terminates
	 * @param {integer} code
	 * @param {string} signal
	 */
	scriptExit(code, signal) {
		console.log(`[${this.settings.agi_port}] Script ended with code ${code} and signal ${signal}`);
		this.exited = true;
		this.emit('exit', code, signal);
	}

	/**
	 * Called when the script sends data from STDOUT
	 * @param {string} data String of data from STDOUT from Script
	 */
	scriptStdout(data) {
		console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] <<< ${data.toString('utf8').trim()}`);
		this.emit('data', data);
	}

	/**
	 * Called when data needs to be sent to the script over STDIN
	 * @param {string} data String of data to send to STDIN to script
	 */
	scriptStdin(data) {
		if(!this.exited && data.trim() === 'HANGUP') {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>> SENT HANGUP TO RUNNING AGI, KILLING AGI `);
			//channel is now dead so hangup
			this.kill();
		} else if(!this.exited) {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>> ${data.trim()}`);
			this.agiScript.stdin.write(data+"\n");
		} else if(this.exited) {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>TRIED TO SEND TO DEAD AGI>> ${data.trim()} `);
		} else {
			console.error(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] Strange state?????????`);
		}
	}
}

module.exports = AGI