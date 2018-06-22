<?php

namespace FreePBX\modules\Core\Backup;

class Routing extends Corebase{
    public function getConfigs(){
        $final = [];
        $routing = new FreePBX\modules\Core\Components\Outboundrouting($this->FreePBX->Database);
        $routes = $routing->listRoutes();
        foreach($routes as $route){
            $route['patterns'] = $routing->getRoutePatternsByID($route['route_id']);
            $final[] = $route;
        }
        return $final;
    }
    public function getFiles(){
    }
    public function getDirs(){
    }
}