<?php 
namespace FreepPBX\Core\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\Core;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

class DidsGQLTest extends ApiBaseTestCase {
	protected static $core;
    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass() {
      parent::setUpBeforeClass();
      self::$core = self::$freepbx->core;
    }
    /**
     * tearDownAfterClass
     *
     * @return void
     */
    public static function tearDownAfterClass() {
      parent::tearDownAfterClass();
    }

	public function test_allInboundRoutes_return_true() {
		$response = $this->request("query {
		    allInboundRoutes{
				inboundRoutes {
						destinationConnection
					 }
				}
		  }");
		$json = (string)$response->getBody();
		$this->assertEquals('{"data":{"allInboundRoutes":{"inboundRoutes":[{"destinationConnection":"Extensions: 9919988 FreePBXUCPTemplateCreator"},{"destinationConnection":"Queues:111 kg-test"}]}}}', $json);
		//status 200 success check
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_allInboundRoutes_return_false() {
		$mockHelper = $this->getMockBuilder(\Freepbx\modules\Core::class)
      	->disableOriginalConstructor()->setMethods(array('getAllDIDs'))->getMock();

		$mockHelper->method('getAllDIDs')->willReturn([]);

		self::$freepbx->Core = $mockHelper;
		$response = $this->request("query {
			allInboundRoutes {
				inboundRoutes {
					destinationConnection
				}
			}
		}");
		$json = (string)$response->getBody();
    	$this->assertEquals('{"data":{"allInboundRoutes":{"inboundRoutes":[]}}}', $json);
		$this->assertEquals(200, $response->getStatusCode()); 
	}
}
