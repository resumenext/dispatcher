<?php

namespace ResumeNext\Dispatcher;

use Iterator;

/**
 * Iterator that invokes a callback for each value
 */
class CallbackIteratorIterator implements Iterator
{
	/** @var callable */
	protected $callback;

	/** @var \Iterator */
	protected $iterator;

	/**
	 * Handle object cloning
	 */
	public function __clone()
	{
		$this->iterator = clone $this->iterator;
	}

	/**
	 * Constructor
	 *
	 * @param \Iterator $iterator Inner iterator
	 * @param callable  $callback function(mixed $value): mixed
	 */
	public function __construct(Iterator $iterator, callable $callback)
	{
		$this->callback = $callback;
		$this->iterator = $iterator;
	}

	public function current()
	{
		return call_user_func($this->callback, $this->iterator->current());
	}

	public function key()
	{
		return $this->iterator->key();
	}

	public function next()
	{
		$this->iterator->next();
	}

	public function rewind()
	{
		$this->iterator->rewind();
	}

	public function valid()
	{
		return $this->iterator->valid();
	}
}
