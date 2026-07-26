<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Resources;

use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class MediaResourcesTest extends TestCase
{

	public function testGetImageFilenameStripsPathTraversal(): void
	{
		$mediaResources = new class ('images', 'https://static.example', '/var/www') extends MediaResources {

			#[Override]
			protected function getSubDir(): string
			{
				return 'slides';
			}

		};
		Assert::same('/var/www/images/slides/42/hash.png', $mediaResources->getImageFilename(42, 'hash.png'));
		Assert::same('/var/www/images/slides/42/passwd', $mediaResources->getImageFilename(42, '../../../etc/passwd'));
	}

}

TestCaseRunner::run(MediaResourcesTest::class);
