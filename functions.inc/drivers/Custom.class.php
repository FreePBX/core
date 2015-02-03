<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Custom extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "custom",
			"hardware" => "custom_custom",
			"prettyName" => _("Generic Custom Driver"),
			"description" => _("Other (Custom) Device"),
			"asteriskSupport" => ">=1.0"
		);
	}

	public function addDevice($id, $settings) {
		$sql = 'INSERT INTO custom (id, keyword, data) values (?,?,?)';
		$sth = $this->database->prepare($sql);
		foreach($settings as $key => $setting) {
			try {
				$sth->execute(array($id,$key,$setting['value']));
			} catch(\Exception $e) {
				die_freepbx($e->getMessage()."<br><br>".'error adding to DAHDI table');
			}
		}
		return true;
	}

	public function delDevice($id) {
		$sql = "DELETE FROM custom WHERE id = ?";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array($id));
		} catch(\Exception $e) {
			die_freepbx($e->getMessage().$sql);
		}
		return true;
	}

	public function getDevice($id) {
		$sql = "SELECT keyword,data FROM custom WHERE id = ?";
		$sth = $this->database->prepare($sql);
		$tech = array();
		try {
			$sth->execute(array($id));
			$tech = $sth->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
			//reformulate into what is expected
			//This is in the try catch just for organization
			foreach($tech as &$value) {
				$value = $value[0];
			}
		} catch(\Exception $e) {}

		return $tech;
	}

	public function getDisplay($display, $deviceInfo, $currentcomponent) {
		$tmparr = array();
		$tt = _("How to dial this device. This will be device specific. For example, a custom device which is really a remote SIP URI might be configured such as SIP/joe@somedomain.com");
		$tmparr['dial'] = array('value' => '', 'tt' => $tt, 'level' => 0);
		unset($tmparr);
		$devopts = $tmparr;
		return $devopts;
	}
}
