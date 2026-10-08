<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use Override;
use ReflectionClass;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

/**
 * A violation a fetched response had, by the class' short name, so the table reads `SecurityTxtNoExpires` rather than
 * a namespace.
 */
final readonly class StatisticsIssue implements StatisticsBucket
{

	private string $violation;


	public function __construct(SecurityTxtSpecViolation $violation)
	{
		$this->violation = new ReflectionClass($violation)->getShortName();
	}


	#[Override]
	public function getMetric(): StatisticsMetric
	{
		return StatisticsMetric::Issue;
	}


	#[Override]
	public function getBucket(): string
	{
		return $this->violation;
	}

}
