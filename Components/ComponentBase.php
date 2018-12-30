<?php
namespace FreePBX\modules\Core\Components;

use PDO;
use Exception;
class ComponentBase{
    public function __construct($logger = null){
        $this->Database = \FreePBX::Database();
        $this->logger = $logger;
        if(empty($logger)){
            $this->logger = new \Psr\Log\NullLogger();
        }
    }
}