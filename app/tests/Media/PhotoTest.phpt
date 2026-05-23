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
		$sizes = ['large' => 'photo-large.jpg', 'small' => 'photo-small.jpg'];
		$photo = new Photo('A Title', 'photo.jpg', $description, $sizes);
		Assert::same('A Title', $photo->getTitle());
		Assert::same('photo.jpg', $photo->getFile());
		Assert::same($description, $photo->getDescription());
		Assert::same($sizes, $photo->getSizes());
	}

}

TestCaseRunner::run(PhotoTest::class);
