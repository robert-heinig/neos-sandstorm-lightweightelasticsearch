<?php
declare(strict_types=1);

namespace Sandstorm\LightweightElasticsearch\Query\Aggregation;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 *
 * Example usage:
 *
 * ```fusion
 * nodeTypesFacet = Neos.Fusion:Component {
 *     termsAggregationResult = ${searchRequest.execute().aggregation("nodeTypes")}
 *     renderer = afx`
 *             <Neos.Fusion:Loop items={props.termsAggregationResult.buckets} itemName="bucket">
 *                 <Neos.Neos:NodeLink node={documentNode} addQueryString={true} arguments={props.termsAggregationResult.buildUriArgumentForFacet(bucket.key)}>{bucket.key}</Neos.Neos:NodeLink> {bucket.doc_count}
 *             </Neos.Fusion:Loop>
 *     `
 * }
 * ```
 *
 * @Flow\Proxy(false)
 */
class TermsAggregationResult implements AggregationResultInterface, ProtectedContextAwareInterface
{
    private array $aggregationResponse;
    private TermsAggregationBuilder $termsAggregationBuilder;

    private function __construct(array $aggregationResponse, TermsAggregationBuilder $aggregationRequestBuilder)
    {
        $this->aggregationResponse = $aggregationResponse;
        $this->termsAggregationBuilder = $aggregationRequestBuilder;
    }

    public static function create(array $aggregationResponse, TermsAggregationBuilder $aggregationRequestBuilder): self
    {
        return new self($aggregationResponse, $aggregationRequestBuilder);
    }

    public function getBuckets() {

        $buckets = $this->aggregationResponse['buckets'];

        $subAggregation = $this->termsAggregationBuilder->getSubAggregation();
        if ($subAggregation instanceof TermsAggregationBuilder) {
            $subAggregationFieldName = $subAggregation->getFieldName();
            foreach ($buckets as $index => $bucket) {

                if (isset($bucket[$subAggregationFieldName])) {
                    $bucket['subAggregation'] = self::create($bucket[$subAggregationFieldName], $subAggregation);
                } else {
                    $bucket['subAggregation'] = null;
                }

                $buckets[$index] = $bucket;
            }
        }

        return $buckets;

    }

    /**
     * @param string|null $value
     * @return bool
     */
    public function isSelectedValue(?string $value = null): bool
    {
        return $this->termsAggregationBuilder->isSelectedValue($value);
    }

    public function noSelectedValues(): bool {
        return !$this->termsAggregationBuilder->hasSelectedValues();
    }

    public function hasSelectedValues(): bool {
        return $this->termsAggregationBuilder->hasSelectedValues();
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
