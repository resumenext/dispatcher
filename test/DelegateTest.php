<?php

namespace ResumeNext\DispatcherTest;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ResumeNext\Dispatcher\Delegate;

/**
 * @coversDefaultClass \ResumeNext\Dispatcher\Delegate
 */
class DelegateTest extends TestCase {

	public static function setupBeforeClass() {
		require_once __DIR__ . "/../src/Delegate.php";
	}

	/**
	 * @covers ::__construct
	 *
	 * @return void
	 */
	public function testConstruct() {
		$callback = function() {
			$this->assertTrue(false);
		};

		$sut = new Delegate($callback);

		$this->assertAttributeSame($callback, "callback", $sut);
	}

	/**
	 * @covers ::process
	 *
	 * @return array
	 */
	public function testProcess() {
		$class = new ReflectionClass(Delegate::class);
		$instance = $class->newInstanceWithoutConstructor();
		$property = $class->getProperty("callback");
		$mock = $this->getMockBuilder(ArrayObject::class)
			->setMethods(["getOffset"])
			->getMock();
		$stub = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$return = (object)[];

		$mock->expects($this->once())
			->method("getOffset")
			->with($this->identicalTo($stub))
			->will($this->returnValue($return));

		$property->setAccessible(true);
		$property->setValue($instance, [$mock, "getOffset"]);

		$result = $instance->process($stub);

		return [$return, $result];
	}

	/**
	 * @coversNothing
	 * @depends testProcess
	 * @param array $return
	 *
	 * @return void
	 */
	public function testProcessReturn($return) {
		$hash0 = spl_object_hash($return[0]);
		$hash1 = spl_object_hash($return[1]);

		$this->assertSame($hash0, $hash1);
	}

}
