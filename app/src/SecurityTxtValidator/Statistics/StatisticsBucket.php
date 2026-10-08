<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

/**
 * A counter's name: the metric and the bucket of it. The bucket answers which metric it belongs to, so a call site
 * cannot count it under the wrong one.
 */
interface StatisticsBucket
{

	public function getMetric(): StatisticsMetric;


	public function getBucket(): string;

}
