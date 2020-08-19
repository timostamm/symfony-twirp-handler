<?php


namespace SymfonyTwirpHandler;


use Example\SearchServiceInterface;
use PHPUnit\Framework\TestCase;

class InterfaceReflectionTest extends TestCase
{


    public function testCreateFromClass()
    {
        $this->expectExceptionMessage('not an interface');
        InterfaceReflection::create(self::class);
    }


    public function testCreate(): InterfaceReflection
    {
        $rs = InterfaceReflection::create(SearchServiceInterface::class);
        $this->assertNotNull($rs);
        return $rs;
    }


    /**
     * @depends testCreate
     * @param InterfaceReflection $rs
     */
    public function testGetProtobufType(InterfaceReflection $rs)
    {
        $this->assertSame('example.SearchService', $rs->getProtobufType());
    }


    /**
     * @depends testCreate
     * @param InterfaceReflection $rs
     */
    public function testGetMethods(InterfaceReflection $rs)
    {
        $this->assertCount(1, $rs->getMethods());
    }


    /**
     * @depends testCreate
     * @param InterfaceReflection $rs
     */
    public function testGetInterface(InterfaceReflection $rs)
    {
        $this->assertSame(SearchServiceInterface::class, $rs->getName());
    }


    /**
     * @depends testCreate
     * @param InterfaceReflection $rs
     * @return InterfaceMethodReflection
     */
    public function testFindMethod(InterfaceReflection $rs): InterfaceMethodReflection
    {
        $method = $rs->findMethod('SEARCH');
        $this->assertNotNull($method);
        return $method;
    }


    /**
     * @depends testCreate
     * @param InterfaceReflection $rs
     */
    public function testFindMethodCase(InterfaceReflection $rs)
    {
        $this->assertNull($rs->findMethod('SEARCH', true));
    }


    /**
     * @depends testCreate
     * @param InterfaceReflection $rs
     */
    public function testValidateImplementation(InterfaceReflection $rs)
    {
        $rs->validateImplementation(new SearchService());
        $this->assertTrue(true);
    }


    /**
     * @depends testCreate
     * @param InterfaceReflection $rs
     */
    public function testValidateImplementationFailure(InterfaceReflection $rs)
    {
        $this->expectExceptionMessage('Expected service implementation to be an object implementing Example\SearchServiceInterface');
        $rs->validateImplementation(new NotASearchService());
    }


    /**
     * @depends testFindMethod
     * @param InterfaceMethodReflection $method
     */
    public function testMethodGetName(InterfaceMethodReflection $method)
    {
        $this->assertSame('search', $method->getName());

    }


    /**
     * @depends testFindMethod
     * @param InterfaceMethodReflection $method
     */
    public function testMethodGetReturnType(InterfaceMethodReflection $method)
    {
        $this->assertSame('Example\SearchResponse', $method->getReturnType());
    }



    /**
     * @depends testFindMethod
     * @param InterfaceMethodReflection $method
     */
    public function testMethodGetParameterType(InterfaceMethodReflection $method)
    {
        $this->assertSame('Example\SearchRequest', $method->getParameterType());
    }


    /**
     * @depends testFindMethod
     * @param InterfaceMethodReflection $method
     */
    public function testMethodGetParameterName(InterfaceMethodReflection $method)
    {
        $this->assertSame('request', $method->getParameterName());
    }




}
