<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use MichalSpacekCz\Talks\Exceptions\TalkSlideIdDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideNumberDoesNotExistException;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TalkSlidesCollectionTest extends TestCase
{

	public function testAddCountGetByIdByNumber(): void
	{
		$slide1 = new TalkSlide(11, 'slide1', 1, 'slide1.jpg', 'slide1-alt.jpg', null, 'Title 1', Html::fromText('Notes 1'), 'Notes 1', null, null, null);
		$slide2 = new TalkSlide(22, 'slide2', 2, 'slide2.jpg', 'slide2-alt.jpg', null, 'Title 2', Html::fromText('Notes 2'), 'Notes 2', null, null, null);
		$slides = new TalkSlideCollection(123);
		$slides->add($slide1);
		$slides->add($slide2);
		Assert::same(2, $slides->count());
		Assert::same($slide1, $slides->getById(11));
		Assert::same($slide2, $slides->getById(22));
		Assert::same($slide1, $slides->getByNumber(1));
		Assert::same($slide2, $slides->getByNumber(2));
	}


	public function testGetByUnknownId(): void
	{
		$slides = new TalkSlideCollection(123);
		Assert::exception(function () use ($slides) {
			$slides->getById(1);
		}, TalkSlideIdDoesNotExistException::class);
	}


	public function testGetByUnknownNumber(): void
	{
		$slides = new TalkSlideCollection(123);
		Assert::exception(function () use ($slides) {
			$slides->getByNumber(1);
		}, TalkSlideNumberDoesNotExistException::class);
	}

}

TestCaseRunner::run(TalkSlidesCollectionTest::class);
