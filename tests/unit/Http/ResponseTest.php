<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Psr\Http\Message\ResponseInterface;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\UnitTest;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;

    class ResponseTest extends UnitTest
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;

        /**
         * @var ResponseFactory
         */
        private $factory;

        /**
         * @var Response
         */
        private $response;

        protected function setUp() : void
        {

            parent::setUp();

            $this->factory = $this->createResponseFactory();
            $this->response = $this->factory->make();
        }

        public function testIsPsrResponse () {

            $response = $this->factory->createResponse();

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertInstanceOf(Response::class, $response);

        }

        public function testIsImmutable () {

            $response1 = $this->factory->createResponse();
            $response2 = $response1->withHeader('foo', 'bar');

            $this->assertNotSame($response1, $response2);
            $this->assertTrue($response2->hasHeader('foo'));
            $this->assertFalse($response1->hasHeader('foo'));

        }

        public function testNotPsrAttributesAreCopiedForNewInstances () {

            $response1 = $this->factory->createResponse();
            $response1->foo = 'bar';

            $response2 = $response1->withHeader('foo', 'bar');

            $this->assertNotSame($response1, $response2);
            $this->assertTrue($response2->hasHeader('foo'));
            $this->assertFalse($response1->hasHeader('foo'));

            $this->assertSame('bar', $response1->foo);
            $this->assertSame('bar', $response2->foo);

        }

        public function testHtml()
        {

            $stream = $this->factory->createStream('foo');

            $response = $this->factory->createResponse()->html($stream);

            $this->assertSame('text/html', $response->getHeaderLine('content-type'));
            $this->assertSame('foo', $response->getBody()->__toString());

        }

        public function testJson()
        {

            $stream = $this->factory->createStream(json_encode(['foo' => 'bar']));

            $response = $this->factory->createResponse()->json($stream);

            $this->assertSame('application/json', $response->getHeaderLine('content-type'));
            $this->assertSame(['foo' => 'bar'], json_decode($response->getBody()->__toString(), true ));


        }

        public function testNoIndex () {

            $response = $this->response->noIndex();
            $this->assertSame('noindex', $response->getHeaderLine('x-robots-tag'));

            $response = $this->response->noIndex('googlebot');
            $this->assertSame('googlebot: noindex', $response->getHeaderLine('x-robots-tag'));

        }

        public function testNoFollow () {

            $response = $this->response->noFollow();
            $this->assertSame('nofollow', $response->getHeaderLine('x-robots-tag'));

            $response = $this->response->noFollow('googlebot');
            $this->assertSame('googlebot: nofollow', $response->getHeaderLine('x-robots-tag'));

        }

        public function testNoRobots()
        {

            $response = $this->response->noRobots();
            $this->assertSame('none', $response->getHeaderLine('x-robots-tag'));

            $response = $this->response->noRobots('googlebot');
            $this->assertSame('googlebot: none', $response->getHeaderLine('x-robots-tag'));



        }

        public function testNoArchive()
        {

            $response = $this->response->noArchive();
            $this->assertSame('noarchive', $response->getHeaderLine('x-robots-tag'));

            $response = $this->response->noArchive('googlebot');
            $this->assertSame('googlebot: noarchive', $response->getHeaderLine('x-robots-tag'));


        }

    }