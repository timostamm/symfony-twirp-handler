<?php


namespace SymfonyTwirpHandler;

use Example\SearchRequest;
use PHPUnit\Framework\TestCase;


class JsonTest extends TestCase
{

    public function testPhpImplementationDoesNotIncludeDefaultValuesInJson()
    {
        $r = new SearchRequest();
        $json = $r->serializeToJsonString();
        $this->assertSame('{}', $json);
    }

    public function testPhpImplementationReallyDoesNotIncludeDefaultValuesInJson()
    {
        $r = new SearchRequest();
        $r->setText('');
        $json = $r->serializeToJsonString();
        $this->assertSame('{}', $json);
    }


}
