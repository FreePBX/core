<?php
namespace FreePBX\modules\Core\Components;

use PDO;
use Exception;
class ComponentBase{
    public function __construct($database, $logger = null ){
        $this->Database = $database;
        $this->logger = $logger;
        if(empty($logger)){
            $this->logger = new \Psr\Log\NullLogger();
        }
    }
}