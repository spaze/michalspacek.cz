<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use Override;

/**
 * What a fetched response amounted to.
 */
enum StatisticsVerdict: string implements StatisticsBucket
{

	case Valid = 'valid';
	case ValidWithWarnings = 'valid_with_warnings';
	case Invalid = 'invalid';
	case NotFound = 'not_found';
	case FetchFailed = 'fetch_failed';


	#[Override]
	public function getMetric(): StatisticsMetric
	{
		return StatisticsMetric::Verdict;
	}


	#[Override]
	public function getBucket(): string
	{
		return $this->value;
	}

}
