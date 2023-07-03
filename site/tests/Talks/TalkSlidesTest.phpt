<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Talks\Exceptions\UnknownSlideException;
use MichalSpacekCz\Test\Database\Database;
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

		$this->database->setFetchFieldResult(null);
		Assert::same(303, $this->talkSlides->getSlideNo(1, '303'));

		$this->database->setFetchFieldResult(null);
		Assert::exception(function (): void {
			$this->talkSlides->getSlideNo(1, 'yo');
		}, UnknownSlideException::class, "Unknown slide 'yo' for talk id '1'");

		$this->database->setFetchFieldResult(808);
		Assert::same(808, $this->talkSlides->getSlideNo(1, 'yo'));

		$this->database->setFetchFieldResult('808');
		Assert::exception(function (): void {
			$this->talkSlides->getSlideNo(1, 'yo');
		}, ShouldNotHappenException::class, "Slide number for slide 'yo' of '1' is a string not an integer");
	}

}

$runner->run(TalkSlidesTest::class);
