<?php

namespace ResumeNext\Dispatcher;

use Interop\Http\ServerMiddleware\{DelegateInterface, MiddlewareInterface};
use Iterator;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * PSR-15 middleware dispatcher
 *
 * Can be reused between and during requests, it's safe to
 * dispatch middleware while you're dispatching middleware.
 * (Assuming the given middleware instances are also safe.)
 */
class Dispatcher implements DispatcherInterface, MiddlewareInterface {

	/** @var \Iterator */
	protected $middleware;

	/** @var \Psr\Http\Message\ServerRequestInterface */
	protected $request;

	/**
	 * Handle object cloning
	 *
	 * @return void
	 */
	public function __clone() {
		$this->middleware = clone $this->middleware;

		$this->middleware->rewind();
	}

	/**
	 * Constructor
	 *
	 * @param \Iterator $middleware Values must be instances of MiddlewareInterface
	 */
	public function __construct(Iterator $middleware) {
		$this->middleware = $middleware;
	}

	/**
	 * Create a Delegate object
	 *
	 * @param callable $callback       Invoked on process()
	 * @param string   $implementation Name of Delegate class
	 *
	 * @return \Interop\Http\ServerMiddleware\DelegateInterface
	 */
	protected function createDelegate(
		callable $callback,
		string $implementation = Delegate::class
	): DelegateInterface {
		return new $implementation($callback);
	}

	public function dispatch(ServerRequestInterface $request): ResponseInterface {
		return $this->run($request);
	}

	/**
	 * Get a middleware from the iterator
	 *
	 * @return \Interop\Http\ServerMiddleware\MiddlewareInterface
	 */
	protected function getMiddleware() {
		$ret = null;

		if ($this->middleware->valid()) {
			$ret = $this->middleware->current();

			$this->middleware->next();
		}

		return $ret;
	}

	/**
	 * Get the current ServerRequestInterface object
	 *
	 * @return \Psr\Http\Message\ServerRequestInterface
	 */
	protected function getRequest() {
		return $this->request;
	}

	public function process(
		ServerRequestInterface $request,
		DelegateInterface $delegate
	) {
		return $this->run($request, $delegate);
	}

	/**
	 * Start the middleware pipeline
	 *
	 * If a delegate is given, will pass on
	 * the possibly modified request object
	 * when the iterator is no longer valid.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface              $request
	 * @param \Interop\Http\ServerMiddleware\DelegateInterface|null $delegate
	 * @param \ResumeNext\Dispatcher\Dispatcher|null            $that
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \ResumeNext\Dispatcher\Exception\OutOfMiddlewareException
	 */
	protected function run(
		ServerRequestInterface $request,
		DelegateInterface $delegate = null,
		Dispatcher $that = null
	): ResponseInterface {
		$that = $that ?: clone $this;

		$that->setRequest($request);

		try {
			$response = $that->step();
		}
		catch (Exception\OutOfMiddlewareException $ex) {
			if ($delegate === null) {
				throw $ex;
			}

			$response = $delegate->process(
				$that->getRequest()
			);
		}

		return $response;
	}

	/**
	 * Set the current ServerRequestInterface object
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 *
	 * @return $this
	 */
	protected function setRequest(ServerRequestInterface $request) {
		$this->request = $request;

		return $this;
	}

	/**
	 * Invoke the next middleware in the pipeline
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \ResumeNext\Dispatcher\Exception\OutOfMiddlewareException
	 */
	protected function step(): ResponseInterface {
		$current = $this->getMiddleware();

		if ($current !== null) {
			$delegate = $this->createDelegate(
				function(ServerRequestInterface $request) {
					return $this->setRequest($request)->step();
				}
			);

			$request = $this->getRequest();

			return $current->process($request, $delegate);
		}

		throw new Exception\OutOfMiddlewareException(
			"Middleware iterator exhausted."
		);
	}

}

/* vi:set ts=4 sw=4 noet: */
