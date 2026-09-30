<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use Override;

/**
 * How far off a fetched `Expires` was.
 */
enum StatisticsExpires: string implements StatisticsBucket
{

	case Expired = 'expired';
	case WithinMonth = 'within_month';
	case WithinYear = 'within_year';
	case OverYear = 'over_year';


	#[Override]
	public function getMetric(): StatisticsMetric
	{
		return StatisticsMetric::Expires;
	}


	#[Override]
	public function getBucket(): string
	{
		return $this->value;
	}

}
