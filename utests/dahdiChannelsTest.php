<?php
namespace FreepPBX\Core\utests;
use FreePBX\modules\Core\Components\Dahdichannels;
use PHPUnit_Framework_TestCase;
use PDO;
use PDOStatement;
use Exception;
/**
 
 * https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
 
 * @backupGlobals disabled
 
 */

class dahdiChannelsTest extends PHPUnit_Framework_TestCase {
    static $testData = [
        'description' => "Foobar one",
        'did' => '1234567',
        'channel' => 1,
    ];

    public static function setUpBeforeClass(){
        include __DIR__.'/../Components/Dahdichannels.php';
    }

    public function testDahdichannels_add_whenAllIsWell_shouldAddChannelSuccessfully(){
        $dc = new Dahdichannels();

        // delete channels in case it's lingering from an old test
		$dc->delete(1);

        $ret = $dc->add('Foobar one', 1, '1234567');
        $this->assertTrue($ret);
    }

    public function testDahichannels_add_whenStringPassedForChannel_shouldReturnFalse(){
        $dc = new Dahdichannels();

        $ret = $dc->add('Foobar one', "FAIL", '1234567');
        $this->assertFalse($ret);
    }


    public function testDahdichannels_add_whenDuplicateRecordCreated_shouldThrowException(){
        $dc = new Dahdichannels();

        // delete channels in case it's lingering from an old test
		$dc->delete(1);

        // create test record
        $ret = $dc->add('Foobar one', 1, '1234567');

        try{
            $ret = $dc->add('Foobar one', 1, '1234567');   
            $this->fail("Should have thrown an exception");
        } catch (Exception $e){
            $this->assertEquals(23000, $e->getCode());
        }

    }
}