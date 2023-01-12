<?php
declare(strict_types=1);

namespace Sandstorm\LightweightElasticsearch\Query;

use Neos\Flow\Annotations as Flow;
use Flowpack\ElasticSearch\Transfer\Exception\ApiException;
use Sandstorm\LightweightElasticsearch\Query\Highlight\HighlightBuilderInterface;
use Sandstorm\LightweightElasticsearch\Query\Query\SearchQueryBuilderInterface;
use Sandstorm\LightweightElasticsearch\Query\Result\SearchResult;


class SearchRequestBuilder extends AbstractSearchRequestBuilder
{
    /**
     * @Flow\InjectConfiguration("handleElasticsearchExceptions")
     * @var string
     */
    protected $handleElasticsearchExceptions;

    /**
     * Cached search result
     *
     * @var SearchResult|null
     */
    private ?SearchResult $searchResult = null;

    protected array $request = [];

    public function query(SearchQueryBuilderInterface $query): self
    {
        if ($this->searchResult !== null) {
            // we need to reset the search result cache when the builder is mutated
            $this->searchResult = null;
        }

        $this->request['query'] = $query->buildQuery();
        return $this;
    }

    public function from(int $offset): self
    {
        if ($this->searchResult !== null) {
            // we need to reset the search result cache when the builder is mutated
            $this->searchResult = null;
        }

        $this->request['from'] = $offset;
        return $this;
    }

    public function size(int $size): self
    {
        if ($this->searchResult !== null) {
            // we need to reset the search result cache when the builder is mutated
            $this->searchResult = null;
        }

        $this->request['size'] = $size;
        return $this;
    }

    public function minScore(float $minScore): self
    {
        if ($this->searchResult !== null) {
            // we need to reset the search result cache when the builder is mutated
            $this->searchResult = null;
        }

        $this->request['min_score'] = $minScore;
        return $this;
    }

    public function highlight(HighlightBuilderInterface $highlightBuilder): self
    {
        if ($this->searchResult !== null) {
            // we need to reset the search result cache when the builder is mutated
            $this->searchResult = null;
        }

        $this->request['highlight'] = $highlightBuilder->buildHighlightRequestPart();
        return $this;
    }

    /**
     * Fast and very dirty way to add sorting.
     * @param string|null $identifier
     * @return SearchRequestBuilder
     */
    public function sort(?string $identifier = null): self
    {

        switch ($identifier) {
            case 'timeDesc':
                $configuration = [
                    'sort_date_time' => ['order' => 'desc'],
                ];
                break;
            case 'supportCenterScoreDesc':
                $configuration = [
                    'sort_support_center_weight' => ['order' => 'desc'],
                    '_score'                     => ['order' => 'desc'],
                ];
                break;
            default:
                $configuration = [
                    '_score' => ['order' => 'desc'],
                ];
        }

        $this->request['sort'][] = $configuration;

        return $this;

    }

    /**
     * Execute the query and return the SearchResult object as result.
     *
     * You can call this method multiple times; and the request is only executed at the first time; and cached
     * for later use.
     *
     * @throws \Flowpack\ElasticSearch\Exception
     * @throws \Neos\Flow\Http\Exception
     */
    public function execute(): SearchResult
    {
        if ($this->searchResult === null) {
            try {
                $jsonResponse = $this->executeInternal($this->request);
                $this->searchResult = SearchResult::fromElasticsearchJsonResponse($jsonResponse, $this->contextNode);
            } catch (ApiException $exception) {
                if ($this->handleElasticsearchExceptions === 'throw') {
                    throw $exception;
                }

                $this->searchResult = SearchResult::error();
            }
        }
        return $this->searchResult;
    }

    /**
     * DO NOT USE THIS METHOD DIRECTLY; it is implemented to ensure Flowpack.Listable plays well with these objects here.
     *
     * @return int
     * @throws \Flowpack\ElasticSearch\Exception
     * @throws \Neos\Flow\Http\Exception
     * @internal
     */
    public function count(): int
    {
        return $this->execute()->total();
    }

    /**
     * Returns the full request as it is sent to Elasticsearch; useful for debugging purposes.
     *
     * @return array
     */
    public function requestForDebugging(): array
    {
        return $this->request;
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
