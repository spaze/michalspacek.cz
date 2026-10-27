<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class PhotoTest extends TestCase
{

	public function testGetters(): void
	{
		$description = Html::fromText('A photo description');
		$sizes = [new PhotoFile('photo-large.jpg', 1600, 1200), new PhotoFile('photo-small.jpg', 13, 37)];
		$photo = new Photo('A Title', new PhotoFile('photo.jpg', 808, 303), $description, $sizes);
		Assert::same('A Title', $photo->getTitle());
		Assert::same('photo.jpg', $photo->getFile()->file);
		Assert::same(808, $photo->getFile()->width);
		Assert::same(303, $photo->getFile()->height);
		Assert::same($description, $photo->getDescription());
		Assert::same($sizes, $photo->getSizes());
	}

}

TestCaseRunner::run(PhotoTest::class);
