<?php


namespace Sandstorm\LightweightElasticsearch\Query\Aggregation;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Sandstorm\LightweightElasticsearch\Query\Query\SearchQueryBuilderInterface;

/**
 * A Terms aggregation can be used to build faceted search.
 *
 * It needs to be configured using:
 * - the Elasticsearch field name which should be faceted (should be of type "keyword" to have useful results)
 * - The selected value from the request, if any.
 *
 * The Terms Aggregation can be additionally used as search filter.
 *
 * See https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-terms-aggregation.html for the details of usage.
 *
 * @Flow\Proxy(false)
 */
class TermsAggregationBuilder implements AggregationBuilderInterface, SearchQueryBuilderInterface, ProtectedContextAwareInterface
{
    private string $fieldName;
    /**
     * @var array the selected values, as taken from the URL parameters
     */
    private array $selectedValues;

    private ?TermsAggregationBuilder $subAggregation = null;

    public static function create(string $fieldName, array $selectedValues = []): self
    {
        return new self($fieldName, $selectedValues);
    }

    private function __construct(string $fieldName, array $selectedValues = [])
    {
        $this->fieldName = $fieldName;
        $this->selectedValues = $selectedValues;
    }

    public function buildAggregationRequest(): array
    {
        // This is a Terms aggregation, with the field name specified by the user.
        $aggregation = [
            'terms' => [
                'field' => $this->fieldName
            ]
        ];

        if ($this->subAggregation instanceof TermsAggregationBuilder) {
            $aggregation['aggs'] = [
                $this->subAggregation->getFieldName() => $this->subAggregation->buildAggregationRequest(),
            ];
        }

        return $aggregation;
    }

    public function bindResponse(array $aggregationResponse): AggregationResultInterface
    {
        return TermsAggregationResult::create($aggregationResponse, $this);
    }

    public function buildQuery(): array
    {
        // for implementing faceting, we build the restriction query here
        if ($this->selectedValues) {
            return [
                'terms' => [
                    $this->fieldName => $this->selectedValues
                ]
            ];
        }

        // json_encode([]) === "[]"
        // json_encode(new \stdClass) === "{}" <-- we need this!
        return ['match_all' => new \stdClass()];
    }

    /**
     * @param string|null $value
     * @return bool
     */
    public function isSelectedValue(?string $value = null): bool
    {
        return in_array($value, $this->selectedValues, true);
    }

    public function hasSelectedValues(): bool {
        return !empty($this->selectedValues);
    }

    public function getFieldName(): string {
        return $this->fieldName;
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }

    public function getSubAggregation(): ?TermsAggregationBuilder {
        return $this->subAggregation;
    }

    /**
     * @param AggregationBuilderInterface $aggregation
     * @return $this
     */
    public function addAsSubAggregation(AggregationBuilderInterface $aggregation): self {
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->subAggregation = $aggregation;
        return $this;
    }

}
