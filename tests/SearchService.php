<?php


namespace SymfonyTwirpHandler;


use Example\SearchRequest;
use Example\SearchResponse;
use Example\SearchServiceInterface;

class SearchService implements SearchServiceInterface
{

    public function search(SearchRequest $request)
    {
        $response = new SearchResponse();
        $response->setHits(['a', 'b', 'c']);
        return $response;
    }

}
