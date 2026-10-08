<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

enum StatisticsMetric: string
{

	case Volume = 'volume';
	case Verdict = 'verdict';
	case Issue = 'issue';
	case Expires = 'expires';

}
