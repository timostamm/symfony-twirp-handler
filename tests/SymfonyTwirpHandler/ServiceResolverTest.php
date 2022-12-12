<?php


namespace SymfonyTwirpHandler;


use Example\SearchServiceInterface;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class ServiceResolverTest extends TestCase
{

    /** @var ServiceResolver */
    private $resolver;


    protected function setUp(): void
    {
        $this->resolver = new ServiceResolver();
    }


    public function testRegisterDoesNotValidate()
    {
        $this->resolver->registerInstance(SearchServiceInterface::class, new NotASearchService());
        $this->assertTrue(true);
    }


    public function testRegisterTwiceThrows()
    {
        $this->resolver->registerInstance(SearchServiceInterface::class, new NotASearchService());
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('already registered');
        $this->resolver->registerInstance(SearchServiceInterface::class, new NotASearchService());
    }


    public function testValidate()
    {
        $this->resolver->registerInstance(SearchServiceInterface::class, new NotASearchService());
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected service implementation to be an object implementing');
        $this->resolver->validate();
    }


    public function testFind()
    {
        $this->resolver->registerInstance(SearchServiceInterface::class, new SearchService());
        $handler = $this->resolver->findService('example.SEARCHservice');
        $this->assertNotNull($handler);
    }


    public function testFindCase()
    {
        $this->resolver->registerInstance(SearchServiceInterface::class, new SearchService());
        $handler = $this->resolver->findService('example.SEARCHservice', true);
        $this->assertNull($handler);
    }


}
