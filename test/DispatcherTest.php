<?php

namespace ResumeNext\DispatcherTest;

use Closure;
use Interop\Http\ServerMiddleware\{DelegateInterface, MiddlewareInterface};
use Iterator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use ReflectionClass;
use ResumeNext\Dispatcher\Dispatcher;
use ResumeNext\Dispatcher\Exception\OutOfMiddlewareException;

/**
 * @coversDefaultClass \ResumeNext\Dispatcher\Dispatcher
 */
class DispatcherTest extends TestCase {

	public static function setupBeforeClass() {
		require_once __DIR__ . "/../src/Exception/ExceptionInterface.php";
		require_once __DIR__ . "/../src/Exception/RuntimeException.php";
		require_once __DIR__ . "/../src/Exception/OutOfMiddlewareException.php";
		require_once __DIR__ . "/../src/DispatcherInterface.php";
		require_once __DIR__ . "/../src/Dispatcher.php";
	}

	/**
	 * @covers ::__clone
	 *
	 * @return array
	 */
	public function testCloneActuallyClones() {
		$class = new ReflectionClass(Dispatcher::class);
		$iterator = new class($this) extends TestCase {
			public function __clone() {
				$this->that->assertTrue(true);
			}

			public function __construct($that) {
				$this->that = $that;
			}

			public function rewind() {
			}
		};

		$sut = $class->newInstanceWithoutConstructor();
		$property = $class->getProperty("middleware");

		$property->setAccessible(true);
		$property->setValue($sut, $iterator);

		$result = clone $sut;

		return [$iterator, $property->getValue($result)];
	}

	/**
	 * @coversNothing
	 * @depends testCloneActuallyClones
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
	 * @covers ::__clone
	 *
	 * @return void
	 */
	public function testCloneCallsRewind() {
		$class = new ReflectionClass(Dispatcher::class);
		$property = $class->getProperty("middleware");
		$instance = $class->newInstanceWithoutConstructor();
		$mock = $this->getMockBuilder(Iterator::class)
			->getMock();

		$mock->expects($this->once())
			->method("rewind");

		$property->setAccessible(true);
		$property->setValue($instance, $mock);

		clone $instance;
	}

	/**
	 * @covers ::__construct
	 *
	 * @return void
	 */
	public function testConstruct() {
		$stub = $this->getMockBuilder(Iterator::class)
			->getMock();

		$sut = new Dispatcher($stub);

		$this->assertAttributeSame($stub, "middleware", $sut);
	}

	/**
	 * @covers ::dispatch
	 *
	 * @return array
	 */
	public function testDispatchCallsRun() {
		$stubRequest = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$stubResponse = $this->getMockBuilder(ResponseInterface::class)
			->getMock();
		$mockSut = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["run"])
			->getMock();

		$mockSut->expects($this->once())
			->method("run")
			->with($this->identicalTo($stubRequest))
			->will($this->returnValue($stubResponse));

		$result = $mockSut->dispatch($stubRequest);

		return [$stubResponse, $result];
	}

	/**
	 * @coversNothing
	 * @depends testDispatchCallsRun
	 * @param array $result
	 *
	 * @return void
	 */
	public function testDispatchReturnsResponse($result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::process
	 *
	 * @return array
	 */
	public function testProcessCallsRun() {
		$stubDelegate = $this->getMockBuilder(DelegateInterface::class)
			->getMock();
		$stubRequest = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$stubResponse = $this->getMockBuilder(ResponseInterface::class)
			->getMock();
		$mockSut = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["run"])
			->getMock();

		$mockSut->expects($this->once())
			->method("run")
			->with(
				$this->identicalTo($stubRequest),
				$this->identicalTo($stubDelegate)
			)
			->will($this->returnValue($stubResponse));

		$result = $mockSut->process($stubRequest, $stubDelegate);

		return [$stubResponse, $result];
	}

	/**
	 * @coversNothing
	 * @depends testProcessCallsRun
	 * @param array $result
	 *
	 * @return void
	 */
	public function testProcessReturnsResponse($result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::createDelegate
	 *
	 * @return void
	 */
	public function testCreateDelegateReturnsNewInstance() {
		$callback = function() {
			$this->assertTrue(false);
		};

		$implementation = get_class(
			new class() implements DelegateInterface {
				public function process(
					ServerRequestInterface $request
				) {
				}
			}
		);

		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("createDelegate");
		$instance = $class->newInstanceWithoutConstructor();

		$method->setAccessible(true);

		$result = $method->invoke($instance, $callback, $implementation);

		$this->assertInstanceOf($implementation, $result);
	}

	/**
	 * @covers ::createDelegate
	 *
	 * @return void
	 */
	public function testCreateDelegatePassesCallback() {
		$callback = function() {
			$this->assertTrue(false);
		};

		$implementation = get_class(
			new class("pi") extends TestCase implements DelegateInterface {

				public static $that;

				public static $callback;

				public function __construct($callback = null) {
					if (static::$callback !== null) {
						static::$that->assertInstanceOf(
							Closure::class,
							$callback
						);

						$hash0 = spl_object_hash(static::$callback);
						$hash1 = spl_object_hash($callback);

						static::$that->assertSame($hash0, $hash1);
					}
				}

				public function process(
					ServerRequestInterface $request
				) {
				}

			}
		);

		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("createDelegate");
		$instance = $class->newInstanceWithoutConstructor();

		$implementation::$that = $this;
		$implementation::$callback = $callback;

		$method->setAccessible(true);
		$method->invoke($instance, $callback, $implementation);
	}

	/**
	 * @covers ::getMiddleware
	 *
	 * @return mixed
	 */
	public function testGetMiddlewareWithInvalidIterator() {
		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("getMiddleware");
		$property = $class->getProperty("middleware");
		$instance = $class->newInstanceWithoutConstructor();
		$mock = $this->getMockBuilder(Iterator::class)
			->getMock();

		$mock->expects($this->once())
			->method("valid")
			->will($this->returnValue(false));
		$mock->expects($this->never())
			->method("current");
		$mock->expects($this->never())
			->method("key");
		$mock->expects($this->never())
			->method("next");
		$mock->expects($this->never())
			->method("rewind");

		$property->setAccessible(true);
		$property->setValue($instance, $mock);

		$method->setAccessible(true);
		$result = $method->invoke($instance);

		return $result;
	}

	/**
	 * @coversNothing
	 * @depends testGetMiddlewareWithInvalidIterator
	 * @param mixed $result
	 *
	 * @return void
	 */
	public function testGetMiddlewareWithInvalidIteratorReturnsNull($result) {
		$this->assertNull($result);
	}

	/**
	 * @covers ::getMiddleware
	 *
	 * @return array
	 */
	public function testGetMiddlewareWithValidIterator() {
		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("getMiddleware");
		$property = $class->getProperty("middleware");
		$instance = $class->newInstanceWithoutConstructor();
		$return = (object)[];
		$mock = $this->getMockBuilder(Iterator::class)
			->getMock();

		$mock->expects($this->at(0))
			->method("valid")
			->will($this->returnValue(true));
		$mock->expects($this->at(1))
			->method("current")
			->will($this->returnValue($return));
		$mock->expects($this->at(2))
			->method("next");
		$mock->expects($this->never())
			->method("rewind");

		$property->setAccessible(true);
		$property->setValue($instance, $mock);

		$method->setAccessible(true);
		$result = $method->invoke($instance);

		return [$return, $result];
	}

	/**
	 * @coversNothing
	 * @depends testGetMiddlewareWithValidIterator
	 * @param array $result
	 *
	 * @return void
	 */
	public function testGetMiddlewareWithValidIteratorReturnsCurrent($result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::getRequest
	 *
	 * @return void
	 */
	public function testGetRequest() {
		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("getRequest");
		$property = $class->getProperty("request");
		$instance = $class->newInstanceWithoutConstructor();
		$stub = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();

		$property->setAccessible(true);
		$property->setValue($instance, $stub);

		$method->setAccessible(true);

		$result = $method->invoke($instance);

		$hash0 = spl_object_hash($stub);
		$hash1 = spl_object_hash($result);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::run
	 *
	 * @return array
	 */
	public function testRun() {
		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("run");
		$instance = $class->newInstanceWithoutConstructor();
		$request = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->getMock();
		$mock = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["setRequest", "step"])
			->getMock();

		$mock->expects($this->once())
			->method("setRequest")
			->with($this->identicalTo($request))
			->will($this->returnSelf());

		$mock->expects($this->once())
			->method("step")
			->will($this->returnValue($response));

		$method->setAccessible(true);

		$result = $method->invoke($instance, $request, null, $mock);

		return [$response, $result];
	}

	/**
	 * @coversNothing
	 * @depends testRun
	 * @param array $result
	 *
	 * @return void
	 */
	public function testRunReturnsResponse($result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::run
	 * @expectedException \ResumeNext\Dispatcher\Exception\OutOfMiddlewareException
	 * @expectedExceptionCode 42
	 *
	 * @return void
	 */
	public function testRunOutOfMiddleware() {
		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("run");
		$instance = $class->newInstanceWithoutConstructor();
		$request = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->getMock();
		$mock = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["setRequest", "step"])
			->getMock();

		$mock->expects($this->once())
			->method("setRequest")
			->with($this->identicalTo($request))
			->will($this->returnSelf());

		$mock->expects($this->once())
			->method("step")
			->will($this->throwException(new OutOfMiddlewareException("", 42)));

		$method->setAccessible(true);

		$method->invoke($instance, $request, null, $mock);
	}

	/**
	 * @covers ::run
	 *
	 * @return array
	 */
	public function testRunCallsDelegate() {
		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("run");
		$instance = $class->newInstanceWithoutConstructor();
		$request0 = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$request1 = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$response = $this->getMockBuilder(ResponseInterface::class)
			->getMock();
		$delegate = $this->getMockBuilder(DelegateInterface::class)
			->getMock();

		$mock = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["getRequest", "setRequest", "step"])
			->getMock();

		$mock->expects($this->once())
			->method("setRequest")
			->with($this->identicalTo($request0))
			->will($this->returnSelf());

		$mock->expects($this->once())
			->method("step")
			->will($this->throwException(new OutOfMiddlewareException("", 42)));

		$mock->expects($this->once())
			->method("getRequest")
			->will($this->returnValue($request1));

		$delegate->expects($this->once())
			->method("process")
			->with($this->identicalTo($request1))
			->will($this->returnValue($response));

		$method->setAccessible(true);

		$result = $method->invoke($instance, $request0, $delegate, $mock);

		return [$response, $result];
	}

	/**
	 * @coversNothing
	 * @depends testRunCallsDelegate
	 * @param array $result
	 *
	 * @return void
	 */
	public function testRunReturnsDelegateResponse($result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::setRequest
	 *
	 * @return array
	 */
	public function testSetRequest() {
		$class = new ReflectionClass(Dispatcher::class);
		$method = $class->getMethod("setRequest");
		$property = $class->getProperty("request");
		$instance = $class->newInstanceWithoutConstructor();
		$stub = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();

		$property->setAccessible(true);
		$method->setAccessible(true);
		$result = $method->invoke($instance, $stub);

		$hash0 = spl_object_hash($stub);
		$hash1 = spl_object_hash($property->getValue($instance));

		$this->assertSame($hash0, $hash1);

		return [$instance, $result];
	}

	/**
	 * @coversNothing
	 * @depends testSetRequest
	 * @param array $result
	 *
	 * @return void
	 */
	public function testSetRequestReturnsSelf($result) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::step
	 * @expectedException \ResumeNext\Dispatcher\Exception\OutOfMiddlewareException
	 *
	 * @return void
	 */
	public function testStepOutOfMiddleware() {
		$method = (new ReflectionClass(Dispatcher::class))
			->getMethod("step");
		$mockSut = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["getMiddleware"])
			->getMock();

		$mockSut->expects($this->once())
			->method("getMiddleware")
			->will($this->returnValue(null));

		$method->setAccessible(true);
		$method->invoke($mockSut);
	}

	/**
	 * @covers ::step
	 *
	 * @return array
	 */
	public function testStep() {
		$method = (new ReflectionClass(Dispatcher::class))
			->getMethod("step");
		$middleware = $this->getMockBuilder(MiddlewareInterface::class)
			->getMock();
		$delegate = $this->getMockBuilder(DelegateInterface::class)
			->getMock();
		$request0 = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$response0 = $this->getMockBuilder(ResponseInterface::class)
			->getMock();
		$mockSut = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["createDelegate", "getMiddleware", "getRequest"])
			->getMock();
		$callable = null;

		$middleware->expects($this->once())
			->method("process")
			->with(
				$this->identicalTo($request0),
				$this->identicalTo($delegate)
			)
			->will($this->returnValue($response0));

		$mockSut->expects($this->once())
			->method("getMiddleware")
			->will($this->returnValue($middleware));
		$mockSut->expects($this->once())
			->method("createDelegate")
			->with($this->callback(function($arg) use (&$callable) {
				$callable = $arg;

				return is_callable($arg);
			}))
			->will($this->returnValue($delegate));
		$mockSut->expects($this->once())
			->method("getRequest")
			->will($this->returnValue($request0));

		$method->setAccessible(true);
		$result0 = $method->invoke($mockSut);

		// Test the anonymous function here because Closure can't be serialized
		$request1 = $this->getMockBuilder(ServerRequestInterface::class)
			->getMock();
		$response1 = $this->getMockBuilder(ResponseInterface::class)
			->getMock();
		$mockSut = $this->getMockBuilder(Dispatcher::class)
			->disableOriginalConstructor()
			->setMethods(["setRequest", "step"])
			->getMock();

		$mockSut->expects($this->once())
			->method("setRequest")
			->with($this->identicalTo($request1))
			->will($this->returnSelf());
		$mockSut->expects($this->once())
			->method("step")
			->will($this->returnValue($response1));

		$result1 = $callable->call($mockSut, $request1);

		return [[$response0, $result0], [$response1, $result1]];
	}

	/**
	 * @coversNothing
	 * @depends testStep
	 * @param array $results
	 *
	 * @return void
	 */
	public function testStepReturnsMiddlewareResponse($results) {
		$hash0 = spl_object_hash($results[0][0]);
		$hash1 = spl_object_hash($results[0][1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @coversNothing
	 * @depends testStep
	 * @param array $results
	 *
	 * @return void
	 */
	public function testStepDelegateReturnsResponse($results) {
		$hash0 = spl_object_hash($results[1][0]);
		$hash1 = spl_object_hash($results[1][1]);

		$this->assertSame($hash0, $hash1);
	}

}
