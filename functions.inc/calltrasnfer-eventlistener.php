<?php

out("Call Trasnfer Event listener ");
$astman->Events("on");

$astman->add_event_handler("AttendedTransfer", function($event, $data, $server, $port) {
	core_AttendedTransfer($data);
});

$last_db_ping = time();
while (true) {
	if (time() > ($last_db_ping + (60 * 60))) {
		$last_db_ping = time();
	}

	$response = $astman->wait_response(true);
	$reconnects = $astman->reconnects;

	while ($response === false && $reconnects > 0) {
		$astman->disconnect();
		if ($astman->connect($astman->server . ':' . $astman->port, $astman->username, $astman->secret, $astman->events) !== false) {
			$response = true;
		} else {
			if ($reconnects > 1) {
				$astman->log("reconnect command failed, sleeping before next attempt");
				sleep(1);
			} else {
				$astman->log("FATAL: no reconnect attempts left, command permanently failed");
				exit(2);
			}
		}
		$reconnects--;
	}
}


function core_AttendedTransfer($data) {
	global $astman;
	$OrigTransfererChannel = $data['OrigTransfererChannel'];
	$TransfereeChannel = $data['TransfereeChannel'];
	//get the call recording file name from the channel
	$response = $astman->send_request('Command',array('Command'=>"core show channel ".$OrigTransfererChannel));
	$responseArray = explode("\n",trim($response['data']));
	$monitor =  preg_grep("/MIXMONITOR_FILENAME/",$responseArray);
	if(is_array($monitor)&& count($monitor) > 0) {
		$monitor = array_values($monitor);
		$file = explode('MIXMONITOR_FILENAME=',$monitor[0]);
		$filename = $file[1];
	}
	if($filename != ""){
		$re = $astman->mixmonitor($TransfereeChannel, "$filename", "ai(LOCAL_MIXMON_ID)");
		dbug(" Starting AttendedTransfer recording from Channel $TransfereeChannel with existing file $filename");
	}
}
?>
