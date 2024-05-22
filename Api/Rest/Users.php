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
		$freepbx = $this->freepbx;
		$app->get('/users', function ($request, $response, $args) use($freepbx) {
			$users = $freepbx->Core->getAllUsers();
			$response->getBody()->write(json_encode($users));
			return $response->withHeader('Content-Type', 'application/json');
		})->add($this->checkReadScopeMiddleware('users'));

		/**
		 * @verb GET
		 * @returns - a user resource
		 * @uri /core/users/:id
		 */
		$app->get('/users/{id}', function ($request, $response, $args) use($freepbx) {
			$base = $freepbx->Core->getUser($args['id']);

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
			$response->getBody()->write(json_encode($base));
			return $response->withHeader('Content-Type', 'application/json');
		})->add($this->checkReadScopeMiddleware('users'));
	}
}
