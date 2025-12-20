<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Media;

use MichalSpacekCz\Media\SlidesPlatform;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SlidesPlatformTest extends TestCase
{

	public function testTryFromUrl(): void
	{
		Assert::same(SlidesPlatform::SlideShare, SlidesPlatform::tryFromUrl('https://slideshare.net/foo'));
		Assert::same(SlidesPlatform::SlideShare, SlidesPlatform::tryFromUrl('https://www.slideshare.net/foo'));
		Assert::same(SlidesPlatform::SpeakerDeck, SlidesPlatform::tryFromUrl('https://speakerdeck.com/foo'));
		Assert::same(SlidesPlatform::SpeakerDeck, SlidesPlatform::tryFromUrl('https://www.speakerdeck.com/foo'));
		Assert::null(SlidesPlatform::tryFromUrl('https://bar.slideshare.net/foo'));
		Assert::null(SlidesPlatform::tryFromUrl('https://bar.speakerdeck.com/foo'));
		Assert::null(SlidesPlatform::tryFromUrl('https://whatever.example'));
		Assert::null(SlidesPlatform::tryFromUrl(':-/'));
	}


	public function testGetName(): void
	{
		Assert::same(SlidesPlatform::SlideShare->value, SlidesPlatform::SlideShare->getName());
	}

}

TestCaseRunner::run(SlidesPlatformTest::class);
