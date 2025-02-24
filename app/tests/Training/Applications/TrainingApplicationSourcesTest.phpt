<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingApplicationSourcesTest extends TestCase
{

	public function __construct(
		private readonly TrainingApplicationSources $trainingApplicationSources,
	) {
	}


	public function testResolveSource(): void
	{
		Assert::same('jakub-vrana', $this->trainingApplicationSources->resolveSource('By Jakub Vrána'));
		Assert::same('michal-spacek', $this->trainingApplicationSources->resolveSource('By Foo Bar'));
	}


	public function testGetDefaultSource(): void
	{
		Assert::same('michal-spacek', $this->trainingApplicationSources->getDefaultSource());
	}


	public function testGetSourceNameInitials(): void
	{
		Assert::same('MŠ', $this->trainingApplicationSources->getSourceNameInitials('Michal Špaček'));
		Assert::same('MZŠ', $this->trainingApplicationSources->getSourceNameInitials('Michal Zdeněk Špaček'));
		Assert::same('II', $this->trainingApplicationSources->getSourceNameInitials('Internet Info, s.r.o.'));
		Assert::same('II', $this->trainingApplicationSources->getSourceNameInitials('Internet Info s.r.o.'));
		Assert::same('II', $this->trainingApplicationSources->getSourceNameInitials('Internet Info'));
		Assert::same('IISSRO', $this->trainingApplicationSources->getSourceNameInitials('Internet Info spol. s r.o.'));
	}

}

TestCaseRunner::run(TrainingApplicationSourcesTest::class);
