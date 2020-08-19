<?php


namespace SymfonyTwirpHandler;


use Example\SearchRequest;
use Example\SearchResponse;
use Example\SearchServiceInterface;
use Google\Protobuf\StringValue;
use PHPUnit\Framework\TestCase;

class ImplementationReflectionTest extends TestCase
{

    public function testConstructorValidatesImplementation()
    {
        $this->expectExceptionMessage('Expected service implementation to be an object implementing');
        new ImplementationReflection(InterfaceReflection::create(SearchServiceInterface::class), new NotASearchService());
    }


    public function testImplementationThrows()
    {
        $si = new ImplementationReflection(InterfaceReflection::create(SearchServiceInterface::class), new SearchServiceException());
        $this->expectExceptionMessage('search exception');
        $si->invoke('search', new SearchRequest());
    }

    public function testImplementationWrongReturnType()
    {
        $si = new ImplementationReflection(InterfaceReflection::create(SearchServiceInterface::class), new SearchServiceNull());
        $this->expectExceptionMessage('Faulty service implementation. Expected return value of SymfonyTwirpHandler\SearchServiceNull::search() to be a Example\SearchResponse. Got NULL instead');
        $si->invoke('search', new SearchRequest());
    }


    public function testWrongParameterType()
    {
        $si = new ImplementationReflection(InterfaceReflection::create(SearchServiceInterface::class), new SearchService());
        $this->expectExceptionMessage('Expected parameter to be a Example\SearchRequest');
        $si->invoke('search', new StringValue());
    }


    public function testMethodNotFound()
    {
        $si = new ImplementationReflection(InterfaceReflection::create(SearchServiceInterface::class), new SearchService());
        $this->expectExceptionMessage('Method "not-a-method" of service');
        $si->invoke('not-a-method', new StringValue());
    }


    public function testResult()
    {
        $si = new ImplementationReflection(InterfaceReflection::create(SearchServiceInterface::class), new SearchService());
        $request = new SearchRequest([
            'text' => 'foo'
        ]);
        /** @var SearchResponse $response */
        $response = $si->invoke('SEARCH', $request);
        $this->assertInstanceOf(SearchResponse::class, $response);
        $this->assertSame(['a', 'b', 'c'], iterator_to_array($response->getHits()));
    }


}
