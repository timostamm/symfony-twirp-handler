<?php


namespace SymfonyTwirpHandler;


use Example\SearchRequest;
use Example\SearchServiceInterface;

class SearchServiceNull implements SearchServiceInterface
{

    public function search(SearchRequest $request)
    {
        return null;
    }


}
