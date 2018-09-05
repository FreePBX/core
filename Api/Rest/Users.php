<?php
namespace FreePBX\modules\Core\Api\Rest;
use FreePBX\modules\Api\Rest\Base;
class Users extends Base {
	protected $module = 'core';
	public static function getScopes() {
		return [
			'read:users' => [
				'description' => _('Read Core User information'),
			]
		];
	}
	public function setupRoutes($app) {

		/**
		 * @verb GET
		 * @return - a list of users
		 * @uri /core/users
		 */
		$app->get('/users', function ($request, $response, $args) {
			$users = $this->freepbx->Core->getAllUsers();
			return $response->withJson($users);
		})->add($this->checkReadScopeMiddleware('users'));

		/**
		 * @verb GET
		 * @returns - a user resource
		 * @uri /core/users/:id
		 */
		$app->get('/users/{id}', function ($request, $response, $args) {
			$base = $this->freepbx->Core->getUser($args['id']);

			// Now, find their voicemail information.
			$z = file("/etc/asterisk/voicemail.conf");
			foreach ($z as $line) {
				$res = explode("=>", $line);
				if (!isset($res[1]))
				continue;

				if (trim($res[0]) == trim($args['id'])) {
					$base['vm'] = trim($res[1]);
					return $response->withJson($base);
				}
			}

			// No voicemail found.
			return $response->withJson($base);
		})->add($this->checkReadScopeMiddleware('users'));
	}
}
