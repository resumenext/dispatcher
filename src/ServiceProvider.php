<?php

namespace ResumeNext\Dispatcher;

use Interop\Container\ContainerInterface;
use Interop\Container\ServiceProvider as ServiceProviderInterface;
use Iterator;
use SplPriorityQueue;
use Zend\Stdlib\SplPriorityQueue as ZendPriorityQueue;

class ServiceProvider implements ServiceProviderInterface
{
	public function getServices()
	{
		return [
			DispatcherInterface::class       => [$this, "createDispatcher"],
			MiddlewareIteratorService::class => [$this, "createMiddlewareIteratorService"],
		];
	}

	/**
	 * Create an instance of Dispatcher
	 *
	 * @param \Interop\Container\ContainerInterface $container
	 *
	 * @return \ResumeNext\Dispatcher\DispatcherInterface
	 */
	public function createDispatcher(ContainerInterface $container): DispatcherInterface
	{
		return new Dispatcher(
			$this->createIterator($container)
		);
	}

	/**
	 * Create an iterator for use with Dispatcher
	 *
	 * @param \Interop\Container\ContainerInterface $container
	 *
	 * @return \Iterator
	 */
	protected function createIterator(ContainerInterface $container): Iterator
	{
		$iterator = $container->get(MiddlewareIteratorService::class);

		return new CallbackIteratorIterator($iterator, [$container, "get"]);
	}

	/**
	 * Create a default iterator for use with Dispatcher if one doesn't exist
	 *
	 * @param \Interop\Container\ContainerInterface $container
	 *
	 * @return \Iterator
	 */
	public function createMiddlewareIteratorService(
		ContainerInterface $container,
		callable $getPrevious = null
	): Iterator {
		return is_null($getPrevious)
			? $this->createPriorityQueue()
			: call_user_func($getPrevious);
	}

	/**
	 * Create an instance of Zend\Stdlib\SplPriorityQueue or SplPriorityQueue
	 *
	 * @return \SplPriorityQueue
	 */
	protected function createPriorityQueue()
	{
		// ZF2+ fixes the ordering of multiple same priorities
		$implementation = class_exists(ZendPriorityQueue::class)
			? ZendPriorityQueue::class
			: SplPriorityQueue::class;

		$ret = new $implementation();

		$ret->setExtractFlags(SplPriorityQueue::EXTR_DATA);

		return $ret;
	}
}
