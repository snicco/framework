<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Contracts;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    abstract class Authenticator extends Middleware
    {

        /**
         * @param  Request  $request
         * @param  Delegate $next This class can be called as a closure. $next($request)
         *
         * @return SuccessfulLoginResponse If authentication was successful.
         *
         * @throws FailedAuthenticationException
         *
         */
        abstract public function attempt ( Request $request, $next ) :Response;

        public function handle(Request $request, Delegate $next) : ResponseInterface {

            return $this->attempt($request, $next);

        }

        protected function login(\WP_User $user, bool $remember = false ) : SuccessfulLoginResponse
        {

            return new SuccessfulLoginResponse( $this->response_factory->createResponse(), $user, $remember);

        }


    }