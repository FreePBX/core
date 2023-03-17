<?php

namespace FreePBX\modules\Core\Backup;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
class Devices extends Corebase{
	public function getConfigs(){
		return [];
	}
	public function getFiles(){
	return [];
	}
	public function getspecialFiles() {
		global $amp_conf;
		$dbuser = $amp_conf['AMPDBUSER'];
		$dbpass = $amp_conf['AMPDBPASS'];
		$dbname = $amp_conf['AMPDBNAME'];
		$fs = new Filesystem();
		$tmpdir = sys_get_temp_dir().'/coredump';
		$fs->remove($tmpdir);
		$fs->mkdir($tmpdir);
		$tmpfile = $tmpdir."/core_devices.sql";
		$tables = array('devices','sip','dahdi','iax');
		$coretables = implode(' ', $tables);
		$mysqldump = fpbx_which('mysqldump');
		$command = "{$mysqldump} -u{$dbuser} -p{$dbpass} {$dbname} {$coretables} --result-file={$tmpfile}";
		$process= new Process($command);
		$process->disableOutput();
		$process->mustRun();
		$fileObj = new \SplFileInfo($tmpfile);
		return [$tmpfile];
	}
	public function getDirs(){
	return [];
	}
}
