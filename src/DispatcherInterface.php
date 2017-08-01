<?php

namespace ResumeNext\Dispatcher;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

interface DispatcherInterface
{
	/**
	 * Dispatch middleware to handle a request
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
