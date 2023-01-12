<?php
declare(strict_types=1);

namespace Sandstorm\LightweightElasticsearch\Query;

/*
 * This file is part of the Neos.ContentRepository.Search package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Sandstorm\LightweightElasticsearch\Query\Aggregation\AggregationsBuilder;
use Sandstorm\LightweightElasticsearch\Query\Aggregation\TermsAggregationBuilder;
use Sandstorm\LightweightElasticsearch\Query\Highlight\NeosFulltextHighlightBuilder;
use Sandstorm\LightweightElasticsearch\Query\Query\BooleanQueryBuilder;
use Sandstorm\LightweightElasticsearch\Query\Query\NeosFulltextQueryBuilder;
use Sandstorm\LightweightElasticsearch\Query\Query\TermQueryBuilder;

/**
 * Eel Helper to write search queries.
 * Example:
 *
 * Elasticsearch.createRequest(site)
 *   .query(
 *      Elasticsearch.createBooleanQuery()
 *         .should(Elasticsearch.createNeosFulltextQuery(site).fulltext("mein Suchstring")))
 *         .should(...)
 *         .should(...)
 *   )
 *   .aggregation()
 *   .execute()
 */
class ElasticsearchHelper implements ProtectedContextAwareInterface
{
    /**
     * Create a new Search Query builder
     *
     * @param NodeInterface|null $contextNode
     * @param array $additionalIndices
     * @return SearchRequestBuilder
     */
    public function createRequest(NodeInterface $contextNode = null, array $additionalIndices = []): SearchRequestBuilder
    {
        return new SearchRequestBuilder($contextNode, $additionalIndices);
    }

    public function createBooleanQuery(): BooleanQueryBuilder
    {
        return BooleanQueryBuilder::create();
    }

    public function createNeosFulltextQuery(NodeInterface $contextNode): NeosFulltextQueryBuilder
    {
        return NeosFulltextQueryBuilder::create($contextNode);
    }

    public function createTermQuery(string $fieldName, $value): TermQueryBuilder
    {
        return TermQueryBuilder::create($fieldName, $value);
    }

    public function createAggregationRequest(NodeInterface $contextNode = null, array $additionalIndices = []): AggregationRequestBuilder
    {
        return new AggregationRequestBuilder($contextNode, $additionalIndices);
    }

    public function createTermsAggregation(string $fieldName, $selectedValues = null): TermsAggregationBuilder
    {

        if (is_string($selectedValues)) {
            return TermsAggregationBuilder::create($fieldName, [$selectedValues]);
        } elseif (is_array($selectedValues)) {
            return TermsAggregationBuilder::create($fieldName, $selectedValues);
        } elseif ($selectedValues === null) {
            return TermsAggregationBuilder::create($fieldName, []);
        }

        throw new InvalidArgumentTypeException('Argument $selectedValues has to be a string or an array.');

    }

    public function createNeosFulltextHighlight(int $fragmentSize = 150, int $fragmentCount = 2): NeosFulltextHighlightBuilder
    {
        return NeosFulltextHighlightBuilder::create($fragmentSize, $fragmentCount);
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
