<?php

namespace FreePBX\modules\Core\Backup;

class Routing extends Corebase{
    public function getConfigs(){
        $final = [];
        $routing = new \FreePBX\modules\Core\Components\Outboundrouting($this->FreePBX->Database);
        $routes = $routing->listAll();
        foreach($routes as $route){
            $route['patterns'] = $routing->getRoutePatternsByID($route['route_id']);
            $route['trunks'] = $routing->getRouteTrunksById($route['route_id']);
            $final[] = $route;
        }
        return $final;
    }
    public function getFiles(){
	return [];
    }
    public function getDirs(){
	return [];
    }
}
