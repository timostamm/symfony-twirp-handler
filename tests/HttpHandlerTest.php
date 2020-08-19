<?php


namespace SymfonyTwirpHandler;


use Example\SearchRequest;
use Example\SearchResponse;
use Example\SearchServiceInterface;
use Exception;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpHandlerTest extends TestCase
{

    /** @var HttpHandler */
    private $handler;

    /** @var TestLogger */
    private $logger;


    protected function setUp()
    {
        $this->logger = new TestLogger();
        $resolver = new ServiceResolver();
        $resolver->registerInstance(SearchServiceInterface::class, new SearchService());
        $this->handler = new HttpHandler($resolver);
        $this->handler->setLogger($this->logger);
    }


    public function testServiceNotFound()
    {
        $httpResponse = $this->handler->handle(
            'xxx.xx',
            'xxx',
            Request::create('http://localhost', 'PUT')
        );
        $this->assertSame(Response::HTTP_NOT_FOUND, $httpResponse->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $httpResponse->headers->get('content-type'));
        $this->assertSame('Resource not found', $httpResponse->getContent());
    }


    public function testMethodNotFound()
    {
        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'xxx',
            Request::create('http://localhost', 'PUT')
        );
        $this->assertSame(Response::HTTP_NOT_FOUND, $httpResponse->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $httpResponse->headers->get('content-type'));
        $this->assertSame('Resource not found', $httpResponse->getContent());
    }


    public function testMethodNotFoundDebug()
    {
        $this->handler->setDebug(true);
        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'xxx',
            Request::create('http://localhost', 'PUT')
        );
        $this->assertSame(Response::HTTP_NOT_FOUND, $httpResponse->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $httpResponse->headers->get('content-type'));
        $this->assertStringContainsString('Resource not found', $httpResponse->getContent());
        $this->assertStringContainsString('Service: SymfonyTwirpHandler\SearchService', $httpResponse->getContent());
        $this->assertStringContainsString('search(Example\SearchRequest request): Example\SearchResponse', $httpResponse->getContent());
    }


    public function testRequestMethodNotAllowed()
    {
        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'GET')
        );
        $this->assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $httpResponse->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $httpResponse->headers->get('content-type'));
        $this->assertSame('Method GET not allowed.', $httpResponse->getContent());
    }

    public function testRequestMethodNotAllowedDebug()
    {
        $this->handler->setDebug(true);
        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'GET')
        );
        $this->assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $httpResponse->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $httpResponse->headers->get('content-type'));
        $this->assertStringContainsString('Method GET not allowed.', $httpResponse->getContent());
        $this->assertStringContainsString('Service: SymfonyTwirpHandler\SearchService', $httpResponse->getContent());
        $this->assertStringContainsString('Allowed request methods: PATCH, POST, PUT', $httpResponse->getContent());
    }


    public function testBadRequest()
    {
        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'PUT', [], [], [], [], 'garbage')
        );
        $this->assertSame(Response::HTTP_BAD_REQUEST, $httpResponse->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $httpResponse->headers->get('content-type'));
        $this->assertSame('Bad Request', $httpResponse->getContent());
        $this->assertTrue($this->logger->hasErrorThatContains('Bad Request for'));
    }


    public function testBadRequestDebug()
    {
        $this->handler->setDebug(true);
        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'PUT', [], [], [], [], 'garbage')
        );
        $this->assertSame(Response::HTTP_BAD_REQUEST, $httpResponse->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $httpResponse->headers->get('content-type'));
        $this->assertStringContainsString('SymfonyTwirpHandler\SearchService::search', $httpResponse->getContent());
        $this->assertStringContainsString('Google\Protobuf\Internal\GPBDecodeException: Error occurred during parsing: Unexpected wire type', $httpResponse->getContent());
        $this->assertTrue($this->logger->hasErrorThatContains('Bad Request for'));
    }


    /**
     * @throws Exception
     */
    public function testSearch()
    {
        $searchRequest = new SearchRequest([
            'text' => 'foo'
        ]);

        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'PUT', [], [], [], [], $searchRequest->serializeToString())
        );

        $this->assertSame(Response::HTTP_OK, $httpResponse->getStatusCode());
        $this->assertSame('application/protobuf; proto=example.SearchResponse', $httpResponse->headers->get('content-type'));

        $searchResponse = new SearchResponse();
        $searchResponse->mergeFromString($httpResponse->getContent());
        $this->assertCount(3, $searchResponse->getHits());
    }


    /**
     * @throws Exception
     */
    public function testBadImplementation()
    {
        $searchRequest = new SearchRequest([
            'text' => 'foo'
        ]);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('search exception');
        $resolver = new ServiceResolver();
        $resolver->registerInstance(SearchServiceInterface::class, new SearchServiceException());
        $handler = new HttpHandler($resolver);
        $handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'PUT', [], [], [], [], $searchRequest->serializeToString())
        );
    }


}
