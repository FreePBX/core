<?php

namespace FreePBX\modules\Core\Restore;

class Routing extends Corebase{
    public function setConfigs($configs){
        $routing = new \FreePBX\modules\Core\Components\Outboundrouting($this->FreePBX->Database);
        foreach ($configs as $route) {
            $routing->editById($route['route_id'], $route['name'], $route['outcid'], $route['outcid_mode'], $route['password'], $route['emergency_route'], $route['intracompany_route'], $route['mohclass'], $route['time_group_id'], $route['patterns'], [], $route['seq'], $route['dest'], $route['time_mode'], $route['timezone'], $route['calendar_id'], $route['calendar_group_id']);
        }
        return $this;
    }
    public function setFiles(){
        return $this;
    }
    public function setDirs(){
        return $this;
    }
    public function processLegacy($pdo, $data, $tables, $tmpfiledir)
    {
        if (!in_array('outbound_routes', $tables)) {
            return $this;
        }
        $final = [];
        $routing = new \FreePBX\modules\Core\Components\Outboundrouting($pdo);
        $routes = $routing->listAll();
        foreach ($routes as $route) {
            $route['patterns'] = $routing->getRoutePatternsByID($route['route_id']);
            $route['trunks'] = $routing->getRouteTrunksById($route['route_id']);
            $final[] = $route;
        }
        $routing = new \FreePBX\modules\Core\Components\Outboundrouting($this->FreePBX->Database);
        foreach ($final as $route) {
            $routing->editById($route['route_id'], $route['name'], $route['outcid'], $route['outcid_mode'], $route['password'], $route['emergency_route'], $route['intracompany_route'], $route['mohclass'], $route['time_group_id'], $route['patterns'], [], $route['seq'], $route['dest'], $route['time_mode'], $route['timezone'], $route['calendar_id'], $route['calendar_group_id']);
        }
        return $this;
    }
}