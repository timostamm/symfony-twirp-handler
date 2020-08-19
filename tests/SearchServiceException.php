<?php


namespace SymfonyTwirpHandler;


use Example\SearchRequest;
use Example\SearchServiceInterface;
use LogicException;

class SearchServiceException implements SearchServiceInterface
{

    public function search(SearchRequest $request)
    {
        throw new LogicException('search exception');
    }


}
