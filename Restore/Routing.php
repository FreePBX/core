<?php

namespace FreePBX\modules\Core\Restore;

class Routing extends Corebase{
	public function setConfigs($configs){
		$routing = new \FreePBX\modules\Core\Components\Outboundrouting($this->FreePBX->Database);
		foreach ($configs as $route) {
			$routing->editById($route['route_id'], $route['name'], $route['outcid'], $route['outcid_mode'], $route['password'], $route['emergency_route'], $route['intracompany_route'], $route['mohclass'], $route['time_group_id'], $route['patterns'], $route['trunks'], $route['seq'], $route['dest'], $route['time_mode'], $route['timezone'], $route['calendar_id'], $route['calendar_group_id']);
		}
		return $this;
	}
}
