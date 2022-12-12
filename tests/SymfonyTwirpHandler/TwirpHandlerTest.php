<?php


namespace SymfonyTwirpHandler;


use Example\SearchRequest;
use Example\SearchResponse;
use Example\SearchServiceInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TwirpHandlerTest extends TestCase
{

    /** @var TwirpHandler */
    private $handler;

    /** @var TestLogger */
    private $logger;


    protected function setUp(): void
    {
        $resolver = new ServiceResolver();
        $resolver->registerInstance(SearchServiceInterface::class, new SearchService());
        $this->handler = new TwirpHandler($resolver);
    }


    public function testServiceNotFound()
    {
        try {
            $this->handler->handle(
                'xxx.xx',
                'xxx',
                Request::create('http://localhost', 'PUT')
            );
            $this->fail('Missing exception');
        } catch (TwirpError $error) {
            $this->assertSame('Service is unknown.', $error->getMessage());
            $this->assertSame(TwirpError::BAD_ROUTE, $error->getErrorCode());
        }
    }


    public function testMethodNotFound()
    {
        try {
            $this->handler->handle(
                'example.SearchService',
                'xxx',
                Request::create('http://localhost', 'PUT')
            );
            $this->fail('Missing exception');
        } catch (TwirpError $error) {
            $this->assertSame('Method "xxx" is unknown for service SymfonyTwirpHandler\SearchService.', $error->getMessage());
            $this->assertSame(TwirpError::BAD_ROUTE, $error->getErrorCode());
        }
    }


    public function testRequestMethodNotAllowed()
    {
        try {
            $this->handler->handle(
                'example.SearchService',
                'search',
                Request::create('http://localhost', 'GET')
            );
            $this->fail('Missing exception');
        } catch (TwirpError $error) {
            $this->assertSame('Method GET not allowed.', $error->getMessage());
            $this->assertSame(TwirpError::BAD_ROUTE, $error->getErrorCode());
        }
    }


    public function testBadRequest()
    {
        try {
            $this->handler->handle(
                'example.SearchService',
                'search',
                Request::create('http://localhost', 'POST', [], [], [], ["CONTENT_TYPE" => "application/protobuf"], 'garbage')
            );
            $this->fail('Missing exception');
        } catch (TwirpError $error) {
            $this->assertSame('Unable to deserialize Example\SearchRequest from binary format.', $error->getMessage());
            $this->assertSame(TwirpError::MALFORMED, $error->getErrorCode());
        }
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
            Request::create('http://localhost', 'POST', [], [], [], ["CONTENT_TYPE" => "application/protobuf"], $searchRequest->serializeToString())
        );

        $this->assertSame(Response::HTTP_OK, $httpResponse->getStatusCode());
        $this->assertSame('application/protobuf', $httpResponse->headers->get('content-type'));

        $searchResponse = new SearchResponse();
        $searchResponse->mergeFromString($httpResponse->getContent());
        $this->assertCount(3, $searchResponse->getHits());
    }

    /**
     * @throws Exception
     */
    public function testSearchJson()
    {
        $searchRequest = new SearchRequest([
            'text' => 'foo'
        ]);

        $httpResponse = $this->handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'POST', [], [], [], ["CONTENT_TYPE" => "application/json"], $searchRequest->serializeToJsonString())
        );

        $this->assertSame(Response::HTTP_OK, $httpResponse->getStatusCode());
        $this->assertSame('application/json', $httpResponse->headers->get('content-type'));

        $searchResponse = new SearchResponse();
        $searchResponse->mergeFromJsonString($httpResponse->getContent());
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
        $this->expectException(TwirpError::class);
        $this->expectExceptionMessage('search exception');
        $resolver = new ServiceResolver();
        $resolver->registerInstance(SearchServiceInterface::class, new SearchServiceException());
        $handler = new TwirpHandler($resolver);
        $handler->handle(
            'example.SearchService',
            'search',
            Request::create('http://localhost', 'POST', [], [], [], ["CONTENT_TYPE" => "application/protobuf"], $searchRequest->serializeToString())
        );
    }


}
