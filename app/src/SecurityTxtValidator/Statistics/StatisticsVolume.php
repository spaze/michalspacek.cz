<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use Override;

/**
 * How a result reached the page.
 */
enum StatisticsVolume: string implements StatisticsBucket
{

	case Fetched = 'fetched';
	case Cached = 'cached';
	case Stale = 'stale';
	case Pasted = 'pasted';


	#[Override]
	public function getMetric(): StatisticsMetric
	{
		return StatisticsMetric::Volume;
	}


	#[Override]
	public function getBucket(): string
	{
		return $this->value;
	}

}
