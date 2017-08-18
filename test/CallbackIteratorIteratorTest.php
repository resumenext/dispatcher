<?php

namespace ResumeNext\DispatcherTest;

use ArrayObject;
use Iterator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ResumeNext\Dispatcher\CallbackIteratorIterator;

/**
 * @coversDefaultClass \ResumeNext\Dispatcher\CallbackIteratorIterator
 */
class CallbackIteratorIteratorTest extends TestCase {

	public static function setupBeforeClass() {
		require_once __DIR__ . "/../src/CallbackIteratorIterator.php";
	}

	/**
	 * @covers ::__clone
	 *
	 * @return array
	 */
	public function testClone() {
		$class = new ReflectionClass(CallbackIteratorIterator::class);
		$iterator = new class($this) extends TestCase {
			public function __clone() {
				$this->that->assertTrue(true);
			}

			public function __construct($that) {
				$this->that = $that;
			}
		};

		$sut = $class->newInstanceWithoutConstructor();
		$property = $class->getProperty("iterator");

		$property->setAccessible(true);
		$property->setValue($sut, $iterator);

		$result = clone $sut;

		return [$iterator, $property->getValue($result)];
	}

	/**
	 * @coversNothing
	 * @depends testClone
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCloneGotCopy(array $result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertNotEquals($hash0, $hash1);
	}

	/**
	 * @covers ::__construct
	 *
	 * @return void
	 */
	public function testConstruct() {
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();
		$callback = function() {
			$this->assertTrue(false);
		};

		$sut = new CallbackIteratorIterator($iterator, $callback);

		$this->assertFalse(false);

		return compact("sut", "iterator", "callback");
	}

	/**
	 * @coversNothing
	 * @depends testConstruct
	 * @param array $vars
	 *
	 * @return void
	 */
	public function testConstructSetsIterator($vars) {
		extract($vars);

		$this->assertAttributeSame($iterator, "iterator", $sut);
	}

	/**
	 * @coversNothing
	 * @depends testConstruct
	 * @param array $vars
	 *
	 * @return void
	 */
	public function testConstructSetsCallback($vars) {
		extract($vars);

		$this->assertAttributeSame($callback, "callback", $sut);
	}

	/**
	 * @covers ::current
	 *
	 * @return array
	 */
	public function testCurrent() {
		$mockIterator = $this->getMockBuilder(Iterator::class)
			->getMock();
		$mockCallback = $this->getMockBuilder(ArrayObject::class)
			->setMethods(["offsetGet"])
			->getMock();
		$currentValue = (object)[];
		$callbackReturn = (object)[];

		$mockIterator->expects($this->once())
			->method("current")
			->will($this->returnValue($currentValue));
		$mockCallback->expects($this->once())
			->method("offsetGet")
			->with($this->identicalTo($currentValue))
			->will($this->returnValue($callbackReturn));

		$class = new ReflectionClass(CallbackIteratorIterator::class);
		$callback = $class->getProperty("callback");
		$iterator = $class->getProperty("iterator");
		$instance = $class->newInstanceWithoutConstructor();

		$callback->setAccessible(true);
		$iterator->setAccessible(true);
		$callback->setValue($instance, [$mockCallback, "offsetGet"]);
		$iterator->setValue($instance, $mockIterator);

		return [$callbackReturn, $instance->current()];
	}

	/**
	 * @coversNothing
	 * @depends testCurrent
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCurrentReturn($result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::key
	 *
	 * @return string
	 */
	public function testKey() {
		$class = new ReflectionClass(CallbackIteratorIterator::class);
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();

		$iterator->expects($this->once())
			->method("key")
			->will($this->returnValue("42"));

		$property = $class->getProperty("iterator");
		$instance = $class->newInstanceWithoutConstructor();

		$property->setAccessible(true);
		$property->setValue($instance, $iterator);

		return $instance->key();
	}

	/**
	 * @coversNothing
	 * @depends testKey
	 * @param string $key
	 *
	 * @return void
	 */
	public function testKeyReturnsKey($key) {
		$this->assertSame("42", $key);
	}

	/**
	 * @covers ::next
	 *
	 * @return void
	 */
	public function testNext() {
		$class = new ReflectionClass(CallbackIteratorIterator::class);
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();

		$iterator->expects($this->once())
			->method("next");

		$property = $class->getProperty("iterator");
		$instance = $class->newInstanceWithoutConstructor();

		$property->setAccessible(true);
		$property->setValue($instance, $iterator);

		$instance->next();
	}

	/**
	 * @covers ::rewind
	 *
	 * @return void
	 */
	public function testRewind() {
		$class = new ReflectionClass(CallbackIteratorIterator::class);
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();

		$iterator->expects($this->once())
			->method("rewind");

		$property = $class->getProperty("iterator");
		$instance = $class->newInstanceWithoutConstructor();

		$property->setAccessible(true);
		$property->setValue($instance, $iterator);

		$instance->rewind();
	}

	/**
	 * @covers ::valid
	 *
	 * @return bool
	 */
	public function testValid() {
		$class = new ReflectionClass(CallbackIteratorIterator::class);
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();

		$iterator->expects($this->once())
			->method("valid")
			->will($this->returnValue(false));

		$property = $class->getProperty("iterator");
		$instance = $class->newInstanceWithoutConstructor();

		$property->setAccessible(true);
		$property->setValue($instance, $iterator);

		return $instance->valid();
	}

	/**
	 * @coversNothing
	 * @depends testValid
	 * @param bool $result
	 *
	 * @return void
	 */
	public function testValidReturnsBool($result) {
		$this->assertSame(false, $result);
	}

}

/* vi:set ts=4 sw=4 noet: */
