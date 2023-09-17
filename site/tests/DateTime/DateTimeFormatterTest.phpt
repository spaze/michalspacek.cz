<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use DateTimeImmutable;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class DateTimeFormatterTest extends TestCase
{

	private readonly DateTimeImmutable $start;


	public function __construct(
		private readonly DateTimeFormatter $dateTimeFormatter,
		private readonly NoOpTranslator $translator,
	) {
		$this->start = new DateTimeImmutable('2023-08-30 10:00:00');
	}


	public function testLocaleDayDefaultLocale(): void
	{
		Assert::same('cs_CZ', $this->translator->getDefaultLocale());
		Assert::same('30. srpna 2023', $this->dateTimeFormatter->localeDay($this->start));
		Assert::same('30. srpna 2023', $this->dateTimeFormatter->localeDay($this->start), 'cs_CZ');
	}


	public function testLocaleDay(): void
	{
		Assert::same('30. srpna 2023', $this->dateTimeFormatter->localeDay($this->start, 'cs_CZ'));
		Assert::same('August 30, 2023', $this->dateTimeFormatter->localeDay($this->start, 'en_US'));
	}


	public function testLocaleMonth(): void
	{
		Assert::same('srpen 2023', $this->dateTimeFormatter->localeMonth($this->start, 'cs_CZ'));
		Assert::same('August 2023', $this->dateTimeFormatter->localeMonth($this->start, 'en_US'));
	}


	public function testLocaleIntervalDay(): void
	{
		$end = new DateTimeImmutable('2023-08-31 14:00:00');
		Assert::same('30.–31. srpna 2023', $this->dateTimeFormatter->localeIntervalDay($this->start, $end, 'cs_CZ'));
		Assert::same('August 30–31, 2023', $this->dateTimeFormatter->localeIntervalDay($this->start, $end, 'en_US'));

		$end = new DateTimeImmutable('2023-09-01 14:00:00');
		Assert::same('30. srpna – 1. září 2023', $this->dateTimeFormatter->localeIntervalDay($this->start, $end, 'cs_CZ'));
		Assert::same('August 30 – September 1, 2023', $this->dateTimeFormatter->localeIntervalDay($this->start, $end, 'en_US'));

		$end = new DateTimeImmutable('2024-02-01 14:00:00');
		Assert::same('30. srpna 2023 – 1. února 2024', $this->dateTimeFormatter->localeIntervalDay($this->start, $end, 'cs_CZ'));
		Assert::same('August 30, 2023 – February 1, 2024', $this->dateTimeFormatter->localeIntervalDay($this->start, $end, 'en_US'));
	}


	public function testLocaleIntervalMonth(): void
	{
		$end = new DateTimeImmutable('2023-08-31 14:00:00');
		Assert::same('srpen 2023', $this->dateTimeFormatter->localeIntervalMonth($this->start, $end, 'cs_CZ'));
		Assert::same('August 2023', $this->dateTimeFormatter->localeIntervalMonth($this->start, $end, 'en_US'));

		$end = new DateTimeImmutable('2023-09-01 14:00:00');
		Assert::same('srpen–září 2023', $this->dateTimeFormatter->localeIntervalMonth($this->start, $end, 'cs_CZ'));
		Assert::same('August–September 2023', $this->dateTimeFormatter->localeIntervalMonth($this->start, $end, 'en_US'));

		$end = new DateTimeImmutable('2024-02-01 14:00:00');
		Assert::same('srpen 2023 – únor 2024', $this->dateTimeFormatter->localeIntervalMonth($this->start, $end, 'cs_CZ'));
		Assert::same('August 2023 – February 2024', $this->dateTimeFormatter->localeIntervalMonth($this->start, $end, 'en_US'));
	}

}

TestCaseRunner::run(DateTimeFormatterTest::class);
