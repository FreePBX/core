const {spawn} = require('child_process');
const EventEmitter = require('events');

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

	/**
	 * Launch AGI Script
	 */
	launch() {
		console.log(`[${this.settings.agi_port}] Launching ${this.settings.agi_network_script} with args: ${this.args.join(',')}`);
		this.agiScript = spawn(`./${this.settings.agi_network_script}`, this.args, {cwd: this.settings.agi_ASTAGIDIR});
		this.agiScript.stdout.on('data', this.scriptStdout.bind(this));
		this.agiScript.on('exit', this.scriptExit.bind(this));

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
		console.log(`[${this.settings.agi_port}] Script ended with code ${code}`);
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
		if(!this.exited) {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>> ${data.trim()}`);
			this.agiScript.stdin.write(data+"\n");
		} else {
			console.log(`[${this.settings.agi_port}][${this.settings.agi_uniqueid}] >>> ${data.trim()} !!! Exited`);
		}
	}
}

module.exports = AGI