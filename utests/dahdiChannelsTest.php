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

class dahdiChannelsTest extends PHPUnit_Framework_TestCase{
    static $testData = [
        'description' => "Foobar one",
        'did' => '1234567',
        'channel' => 1,
    ];

    public static function setUpBeforeClass(){
        include __DIR__.'/../Components/Dahdichannels.php';
    }

    public function testAdd(){
        $database = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepare'])
            ->getMock();
        $stmt = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'fetchAll'])
            ->getMock();
        $stmt->method('execute')
            ->willReturn(true);
        $database->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $dc = new Dahdichannels($database);
        $ret = $dc->add('Foobar one', 1, '1234567');
        $this->assertTrue($ret);
        $ret = $dc->add('Foobar one', "FAIL", '1234567');
        $this->assertFalse($ret);
    }
    public function testAddException(){
        $database = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepare'])
            ->getMock();
        $stmt = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'fetchAll'])
            ->getMock();
        $stmt->method('execute')
            ->will($this->throwException(new Exception('Stuff',1062)));
        $database->method('prepare')
            ->willReturn($stmt);

        $dc = new Dahdichannels($database);
        $ret = $dc->add('Foobar one', 1, '1234567');
        $this->assertFalse($ret);
        $stmt->method('execute')
            ->will($this->throwException(new Exception('Stuff', 9001)));
        $dc = new Dahdichannels($database);
        try{
            $ret = $dc->add('Foobar one', 1, '1234567');   
            $this->fail("Should have thrown an exception");
        } catch (Exception $e){
            $this->assertTrue(true);
        }

    }
}