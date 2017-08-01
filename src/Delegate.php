<?php

namespace ResumeNext\Dispatcher;

use Interop\Http\ServerMiddleware\DelegateInterface;

class Delegate implements DelegateInterface
{
	/** @var callable */
	protected $callback;

	/**
	 * Constructor
	 *
	 * @param callable $callback
	 */
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	public function process(ServerRequestInterface $request)
	{
		return call_user_func($this->callback, $request);
	}
}