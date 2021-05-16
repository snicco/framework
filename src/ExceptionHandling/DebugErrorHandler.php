<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

	use Throwable;
	use Whoops\RunInterface;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
    use WPEmerge\Http\Response;
    use WPEmerge\Traits\HandlesExceptions;

	class DebugErrorHandler implements ErrorHandlerInterface {

		use HandlesExceptions;

		/** @var \Whoops\RunInterface */
		private $whoops;

		public function __construct( RunInterface $whoops) {

			$this->whoops = $whoops;

		}

		public function handleException( $exception )  {


			$method = RunInterface::EXCEPTION_HANDLER;

			$this->whoops->{$method}( $exception );

			UnrecoverableExceptionHandled::dispatch();


		}

		public function transformToResponse( Throwable $exception ) : ?Response {

			 $this->handleException( $exception);

			 return null;

		}


	}