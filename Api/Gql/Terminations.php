<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Terminations extends Base {
	public function postInitTypes() {
		$destinations = $this->typeContainer->get('destination');
		$destinations->addType($this->typeContainer->get('coreterminations')->getReference());
	}

	public function initTypes() {
		$ssw = $this->typeContainer->create('coreterminations');
		$ssw->setDescription('Destination Termination Types');
		$ssw->addFieldCallback(function() {
			return [
				'id' => [
					'type' => Type::id(),
				],
				'description' => [
					'type' => Type::string(),
					"description" => "Description of the termination type"
				]
			];
		});
	}
}
