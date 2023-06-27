<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TalkSlidesTest extends TestCase
{

	public function __construct(
		private readonly TalkSlides $talkSlides,
		private readonly Database $database,
	) {
	}


	public function testGetSlideNo(): void
	{
		Assert::null($this->talkSlides->getSlideNo(1, null));

		$this->database->setFetchFieldResult(false);
		Assert::same(303, $this->talkSlides->getSlideNo(1, '303'));

		$this->database->setFetchFieldResult(false);
		Assert::throws(function (): void {
			$this->talkSlides->getSlideNo(1, 'yo');
		}, RuntimeException::class, 'Unknown slide yo for talk 1');

		$this->database->setFetchFieldResult(808);
		Assert::same(808, $this->talkSlides->getSlideNo(1, 'yo'));

		$this->database->setFetchFieldResult('808');
		Assert::throws(function (): void {
			$this->talkSlides->getSlideNo(1, 'yo');
		}, ShouldNotHappenException::class, "Slide number for slide 'yo' of '1' is a string not an integer");
	}

}

$runner->run(TalkSlidesTest::class);
