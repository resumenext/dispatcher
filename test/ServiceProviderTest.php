<?php

namespace ResumeNext\DispatcherTest;

use Interop\Container\ContainerInterface;
use Iterator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ResumeNext\Dispatcher\CallbackIteratorIterator;
use ResumeNext\Dispatcher\{DispatcherInterface, Dispatcher};
use ResumeNext\Dispatcher\{MiddlewareIteratorService, ServiceProvider};
use SplPriorityQueue;
use Zend\Stdlib\SplPriorityQueue as ZendPriorityQueue;

/**
 * @coversDefaultClass \ResumeNext\Dispatcher\ServiceProvider
 */
class ServiceProviderTest extends TestCase {

	public static function setupBeforeClass() {
		require_once __DIR__ . "/../src/CallbackIteratorIterator.php";
		require_once __DIR__ . "/../src/DispatcherInterface.php";
		require_once __DIR__ . "/../src/Dispatcher.php";
		require_once __DIR__ . "/../src/ServiceProvider.php";
	}

	/**
	 * @covers ::getServices
	 *
	 * @return array
	 */
	public function testGetServices() {
		$sut = new ServiceProvider();

		$result = $sut->getServices();

		$this->assertInternalType("array", $result);

		return $result;
	}

	/**
	 * @coversNothing
	 * @depends testGetServices
	 * @param array $services
	 *
	 * @return void
	 */
	public function testGetServicesContainsCallables($services) {
		$this->assertContainsOnly("callable", $services, true);
	}

	/**
	 * @covers ::createDispatcher
	 * @uses \ResumeNext\Dispatcher\Dispatcher
	 *
	 * @return array
	 */
	public function testCreateDispatcher() {
		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMock();
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();
		$mockSut = $this->getMockBuilder(ServiceProvider::class)
			->setMethods(["createIterator"])
			->getMock();

		$mockSut->expects($this->once())
			->method("createIterator")
			->with($this->identicalTo($container))
			->will($this->returnValue($iterator));

		$result = $mockSut->createDispatcher($container);

		return [$iterator, $result];
	}

	/**
	 * @coversNothing
	 * @depends testCreateDispatcher
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateDispatcherReturnsDispatcherInterface($result) {
		$this->assertInstanceOf(DispatcherInterface::class, $result[1]);
	}

	/**
	 * @coversNothing
	 * @depends testCreateDispatcher
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateDispatcherResultHasIterator($result) {
		$property = (new ReflectionClass(Dispatcher::class))
			->getProperty("middleware");

		$property->setAccessible(true);

		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($property->getValue($result[1]));

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::createIterator
	 * @uses \ResumeNext\Dispatcher\CallbackIteratorIterator
	 *
	 * @return array
	 */
	public function testCreateIterator() {
		$sut = new ServiceProvider();
		$method = (new ReflectionClass(ServiceProvider::class))
			->getMethod("createIterator");
		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMock();
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();

		$container->expects($this->once())
			->method("get")
			->with($this->equalTo(MiddlewareIteratorService::class))
			->will($this->returnValue($iterator));

		$method->setAccessible(true);

		$result = $method->invoke($sut, $container);

		return [$iterator, $container, $result];
	}

	/**
	 * @coversNothing
	 * @depends testCreateIterator
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateIteratorReturnsIterator($result) {
		$this->assertInstanceOf(Iterator::class, $result[2]);
	}

	/**
	 * @coversNothing
	 * @depends testCreateIterator
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateIteratorResultHasIterator($result) {
		$property = (new ReflectionClass(CallbackIteratorIterator::class))
			->getProperty("iterator");

		$property->setAccessible(true);

		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($property->getValue($result[2]));

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @coversNothing
	 * @depends testCreateIterator
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateIteratorResultHasContainer($result) {
		$property = (new ReflectionClass(CallbackIteratorIterator::class))
			->getProperty("callback");

		$property->setAccessible(true);

		$hash0 = spl_object_hash($result[1]);
		$hash1 = spl_object_hash($property->getValue($result[2])[0]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @coversNothing
	 * @depends testCreateIterator
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateIteratorResultCallable($result) {
		$property = (new ReflectionClass(CallbackIteratorIterator::class))
			->getProperty("callback");

		$property->setAccessible(true);

		$this->assertSame("get", $property->getValue($result[2])[1]);
	}

	/**
	 * @covers ::createMiddlewareIteratorService
	 *
	 * @return array
	 */
	public function testCreateMiddlewareIteratorServiceWithCallable() {
		$sut = new ServiceProvider();
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();
		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMock();
		$getPrevious = function() use ($iterator) {
			$this->assertTrue(true);

			return $iterator;
		};

		$result = $sut->createMiddlewareIteratorService($container, $getPrevious);

		return [$iterator, $result];
	}

	/**
	 * @coversNothing
	 * @depends testCreateMiddlewareIteratorServiceWithCallable
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateMiddlewareIteratorServiceWithCallableReturn(
		$result
	) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::createMiddlewareIteratorService
	 *
	 * @return array
	 */
	public function testCreateMiddlewareIteratorServiceWithoutCallable() {
		$iterator = $this->getMockBuilder(Iterator::class)
			->getMock();
		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMock();
		$mockSut = $this->getMockBuilder(ServiceProvider::class)
			->setMethods(["createPriorityQueue"])
			->getMock();

		$mockSut->expects($this->once())
			->method("createPriorityQueue")
			->will($this->returnValue($iterator));

		$result = $mockSut->createMiddlewareIteratorService($container);

		return [$iterator, $result];
	}

	/**
	 * @coversNothing
	 * @depends testCreateMiddlewareIteratorServiceWithoutCallable
	 * @param array $result
	 *
	 * @return void
	 */
	public function testCreateMiddlewareIteratorServiceWithoutCallableReturn(
		$result
	) {
		$hash0 = spl_object_hash($result[0]);
		$hash1 = spl_object_hash($result[1]);

		$this->assertSame($hash0, $hash1);
	}

	/**
	 * @covers ::createPriorityQueue
	 *
	 * @return void
	 */
	public function testCreatePriorityQueue() {
		$method = (new ReflectionClass(ServiceProvider::class))
			->getMethod("createPriorityQueue");
		$sut = new ServiceProvider();

		$method->setAccessible(true);

		$result = $method->invoke($sut);

		$this->assertInstanceOf(SplPriorityQueue::class, $result);
	}

	/**
	 * @covers ::createPriorityQueue
	 * @runInSeparateProcess
	 *
	 * @return void
	 */
	public function testCreatePriorityQueueWithZendStdlib() {
		$class = get_class(new class() extends SplPriorityQueue {});
		$method = (new ReflectionClass(ServiceProvider::class))
			->getMethod("createPriorityQueue");
		$sut = new ServiceProvider();

		class_alias($class, ZendPriorityQueue::class);

		$method->setAccessible(true);

		$result = $method->invoke($sut);

		$this->assertInstanceOf(ZendPriorityQueue::class, $result);
	}

}
