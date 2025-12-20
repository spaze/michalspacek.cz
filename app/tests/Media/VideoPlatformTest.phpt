<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Media;

use MichalSpacekCz\Media\VideoPlatform;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class VideoPlatformTest extends TestCase
{

	public function testTryFromUrl(): void
	{
		Assert::same(VideoPlatform::YouTube, VideoPlatform::tryFromUrl('https://youtube.com/foo'));
		Assert::same(VideoPlatform::YouTube, VideoPlatform::tryFromUrl('https://www.youtube.com/foo'));
		Assert::same(VideoPlatform::YouTube, VideoPlatform::tryFromUrl('https://youtu.be/foo'));
		Assert::same(VideoPlatform::Vimeo, VideoPlatform::tryFromUrl('https://vimeo.com/foo'));
		Assert::same(VideoPlatform::Vimeo, VideoPlatform::tryFromUrl('https://www.vimeo.com/foo'));
		Assert::same(VideoPlatform::SlidesLive, VideoPlatform::tryFromUrl('https://slideslive.com/foo'));
		Assert::same(VideoPlatform::SlidesLive, VideoPlatform::tryFromUrl('https://www.slideslive.com/foo'));
		Assert::null(VideoPlatform::tryFromUrl('https://bar.youtube.com/foo'));
		Assert::null(VideoPlatform::tryFromUrl('https://bar.vimeo.com/foo'));
		Assert::null(VideoPlatform::tryFromUrl('https://bar.slideslive.com/foo'));
		Assert::null(VideoPlatform::tryFromUrl('https://whatever.example'));
		Assert::null(VideoPlatform::tryFromUrl(':-/'));
	}


	public function testGetName(): void
	{
		Assert::same(VideoPlatform::YouTube->value, VideoPlatform::YouTube->getName());
	}

}

TestCaseRunner::run(VideoPlatformTest::class);
