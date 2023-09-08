<?php
namespace FreePBX\modules\Core\Components;
use PDO;
use Exception;
use PDOException;

#[\AllowDynamicProperties]
class Dahdichannels extends ComponentBase{

    public static function getButtons($request){
        $buttons = [];
        if (!empty($request['extdisplay'])) {
            $buttons['delete'] = [
                'name' => 'delete',
                'id' => 'delete',
                'value' => _('Delete'),
            ];
        }
        $buttons['reset'] = [
            'name' => 'reset',
            'id' => 'reset',
            'value' => _('Reset'),
        ];
        $buttons['submit'] = [
            'name' => 'submit',
            'id' => 'submit',
            'value' => _('Submit')
        ];
        
	if (!isset($request['view'])||$request['view'] == '') {
		$buttons = array();
	}
        return $buttons;

    }

    public function listChannels(){
        $sql = "SELECT * FROM dahdichandids ORDER BY channel";
        $stmt = $this->Database->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get($channel){
        $sql = "SELECT * FROM dahdichandids WHERE channel = :channel LIMIT 1";
        $stmt = $this->Database->prepare($sql);
        $stmt->execute([':channel' => $channel]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add($description, $channel, $did) {
        // A channel can only be a number
        if (!is_numeric($channel)) {
            return false;
        }

        $sql = "INSERT INTO dahdichandids (channel, description, did) VALUES (:channel, :description, :did)";
        $stmt = $this->Database->prepare($sql);
        $stmt->execute([
            ':channel' => $channel,
            ':description' => $description,
            ':did' => $did,
        ]);
        return true;
    }
    public function edit($description, $channel, $did){
        $sql = "UPDATE dahdichandids SET description = :description, did = :did WHERE channel = :channel";
        $stmt = $this->Database->prepare($sql);
        return $stmt->execute([
            ':channel' => $channel,
            ':description' => $description,
            ':did' => $did,
        ]);
    }

    public function delete($channel){
        $sql = "DELETE FROM dahdichandids WHERE channel = :channel";
        $stmt = $this->Database->prepare($sql);
        return $stmt->execute([
            ':channel' => $channel,
        ]);
    }
}
