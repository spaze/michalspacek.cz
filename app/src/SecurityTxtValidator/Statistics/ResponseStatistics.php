<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

/**
 * A violation counts once per response even if it's on multiple lines, so the count says how many files have the
 * problem, not how many lines. Nothing here names the host: the counters say how files are doing, not whose.
 */
final readonly class ResponseStatistics
{

	/**
	 * @return list<StatisticsBucket>
	 */
	public function forCheckHostResult(SecurityTxtCheckHostResult $result): array
	{
		$buckets = [$this->verdict($result), ...$this->issues($result)];
		$expires = $this->expires($result);
		if ($expires !== null) {
			$buckets[] = $expires;
		}
		return $buckets;
	}


	/**
	 * @return list<StatisticsBucket>
	 */
	public function forFetchFailure(SecurityTxtFetcherException $e): array
	{
		return [$e instanceof SecurityTxtNotFoundException ? StatisticsVerdict::NotFound : StatisticsVerdict::FetchFailed];
	}


	private function verdict(SecurityTxtCheckHostResult $result): StatisticsVerdict
	{
		if (!$result->isValid()) {
			return StatisticsVerdict::Invalid;
		}
		if ($result->getFetchWarnings() !== [] || $result->getLineWarnings() !== [] || $result->getFileWarnings() !== []) {
			return StatisticsVerdict::ValidWithWarnings;
		}
		return StatisticsVerdict::Valid;
	}


	/**
	 * @return list<StatisticsIssue>
	 */
	private function issues(SecurityTxtCheckHostResult $result): array
	{
		$violationsByClass = [];
		$violationLists = [
			$result->getFetchErrors(),
			$result->getFetchWarnings(),
			$result->getFileErrors(),
			$result->getFileWarnings(),
			...$result->getLineErrors(),
			...$result->getLineWarnings(),
		];
		foreach ($violationLists as $violations) {
			foreach ($violations as $violation) {
				$violationsByClass[$violation::class] = $violation;
			}
		}
		return array_map(fn(SecurityTxtSpecViolation $violation): StatisticsIssue => new StatisticsIssue($violation), array_values($violationsByClass));
	}


	private function expires(SecurityTxtCheckHostResult $result): ?StatisticsExpires
	{
		$isExpired = $result->getIsExpired();
		$days = $result->getExpiryDays();
		if ($isExpired === null || $days === null) {
			return null;
		}
		return match (true) {
			$isExpired => StatisticsExpires::Expired,
			$days <= 31 => StatisticsExpires::WithinMonth,
			$days <= 366 => StatisticsExpires::WithinYear,
			default => StatisticsExpires::OverYear,
		};
	}

}
