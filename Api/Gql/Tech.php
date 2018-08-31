<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Tech extends Base {

	public function initializeTypes() {
		$tech = $this->typeContainer->create('coretech','union');
		$tech->setDescription('');

		$tech->addResolveType(function($value, $context, $info) use ($tech) {
			foreach($tech->getResolveTypeCallbacks() as $cb) {
				$out = $cb($value, $context, $info);
				if(!is_null($out)) {
					return $out;
				}
			}
			return $this->typeContainer->get('unknowntech')->getObject();
		});

		$tech->addTypeCallback(function() {
			return [
				$this->typeContainer->get('unknowntech')->getObject()
			];
		});

		$unknowntech = $this->typeContainer->create('unknowntech');
		$unknowntech->setDescription('A tech that does not have a GraphQL reference');
		$unknowntech->addFieldCallback(function() {
			return [
				'tech' => [
					'type' => Type::nonNull(Type::id()),
					'description' => 'The unknown tech id',
					'resolve' => function($value, $args, $context, $info) {
						return $value['tech'];
					}
				]
			];
		});

		$pjsip = $this->typeContainer->create('pjsip','object');
		$pjsip->addFieldCallback(function() {
			return [
				'tech' => [
					'type' => Type::nonNull(Type::id()),
					'description' => 'The unknown tech id',
					'resolve' => function($value, $args, $context, $info) {
						return $value['tech'];
					}
				],
				'secret' => [
					'type' => Type::string(),
					'description' => 'Password (secret) configured for the device. Should be alphanumeric with at least 2 letters and numbers to keep secure'
				],
				'dtmfmode' => [
					'type' => Type::string(),
					'description' => 'The DTMF signaling mode used by this device, usually RFC for most phones'
				],
				'account' => [
					'type' => Type::string()
				],
				'accountcode' => [
					'type' => Type::string()
				],
				'aggregate_mwi' => [
					'type' => Type::string()
				],
				'allow' => [
					'type' => Type::string()
				],
				'avpf' => [
					'type' => Type::string()
				],
				'callerid' => [
					'type' => Type::string()
				],
				'context' => [
					'type' => Type::string()
				],
				'defaultuser' => [
					'type' => Type::string()
				],
				'device_state_busy_at' => [
					'type' => Type::string()
				],
				'dial' => [
					'type' => Type::string()
				],
				'disallow' => [
					'type' => Type::string()
				],
				'force_rport' => [
					'type' => Type::string()
				],
				'icesupport' => [
					'type' => Type::string()
				],
				'mailbox' => [
					'type' => Type::string()
				],
				'match' => [
					'type' => Type::string()
				],
				'max_contacts' => [
					'type' => Type::string()
				],
				'maximum_expiration' => [
					'type' => Type::string()
				],
				'media_encryption' => [
					'type' => Type::string()
				],
				'media_encryption_optimistic' => [
					'type' => Type::string()
				],
				'media_use_received_transport' => [
					'type' => Type::string()
				],
				'minimum_expiration' => [
					'type' => Type::string()
				],
				'mwi_subscription' => [
					'type' => Type::string()
				],
				'namedcallgroup' => [
					'type' => Type::string()
				],
				'namedpickupgroup' => [
					'type' => Type::string()
				],
				'outbound_proxy' => [
					'type' => Type::string()
				],
				'qualifyfreq' => [
					'type' => Type::string()
				],
				'rewrite_contact' => [
					'type' => Type::string()
				],
				'rtcp_mux' => [
					'type' => Type::string()
				],
				'rtp_symmetric' => [
					'type' => Type::string()
				],
				'sendrpid' => [
					'type' => Type::string()
				],
				'sipdriver' => [
					'type' => Type::string()
				],
				'timers' => [
					'type' => Type::string()
				],
				'transport' => [
					'type' => Type::string()
				],
				'trustpid' => [
					'type' => Type::string()
				],
			];
		});

		$pjsip->addResolve(function($value, $args, $context, $info) {
			if(empty($this->list[$value['id']])) {
				$sql = "SELECT keyword, data FROM sip WHERE id = :id";
				$sth = $this->freepbx->Database->prepare($sql);
				$sth->execute([":id" => $value['id']]);
				$this->list[$value['id']] = $sth->fetchAll(\PDO::FETCH_KEY_PAIR);
			}
			return isset($this->list[$value['id']][$info->fieldName]) ? $this->list[$value['id']][$info->fieldName] : null;
		});

		$tech->addTypeCallback(function() {
			return [
				$this->typeContainer->get('pjsip')->getObject()
			];
		});

		$tech->addResolveTypeCallback(function($value, $context, $info) {
			try {
				return $this->typeContainer->get($value['tech'])->getObject();
			} catch(\Exception $e) {
				return null;
			}
		});
	}
}
