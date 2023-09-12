<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Media\Resources\InterviewMediaResources;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\Row;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class VideoFactoryTest extends TestCase
{

	private readonly VideoFactory $videoFactory;


	public function __construct(
		InterviewMediaResources $mediaResources,
		SupportedImageFileFormats $supportedImageFileFormats,
	) {
		$this->videoFactory = new VideoFactory($mediaResources, $supportedImageFileFormats, new VideoThumbnails($mediaResources, $supportedImageFileFormats));
	}


	public function testCreateFromDatabaseRow(): void
	{
		$row = new Row();
		$row->id = 123;
		$row->videoHref = 'https://youtube.com/foo';
		$row->videoThumbnail = 'thumb.jpg';
		$row->videoThumbnailAlternative = 'thumb.webp';

		$video = $this->videoFactory->createFromDatabaseRow($row);
		Assert::same('https://youtube.com/foo', $video->getVideoHref());
		Assert::same('thumb.jpg', $video->getThumbnailFilename());
		Assert::same('https://www.domain.example/i/images/interviews/123/thumb.jpg', $video->getThumbnailUrl());
		Assert::same('thumb.webp', $video->getThumbnailAlternativeFilename());
		Assert::same('https://www.domain.example/i/images/interviews/123/thumb.webp', $video->getThumbnailAlternativeUrl());
		Assert::same('image/webp', $video->getThumbnailAlternativeContentType());
		Assert::same(320, $video->getThumbnailWidth());
		Assert::same(180, $video->getThumbnailHeight());
		Assert::same('YouTube', $video->getVideoPlatform());

		$row = new Row();
		$row->id = 123;
		$row->videoHref = null;
		$row->videoThumbnail = null;
		$row->videoThumbnailAlternative = null;

		$video = $this->videoFactory->createFromDatabaseRow($row);
		Assert::null($video->getVideoHref());
		Assert::null($video->getThumbnailFilename());
		Assert::null($video->getThumbnailUrl());
		Assert::null($video->getThumbnailAlternativeFilename());
		Assert::null($video->getThumbnailAlternativeUrl());
		Assert::null($video->getThumbnailAlternativeContentType());
		Assert::same(320, $video->getThumbnailWidth());
		Assert::same(180, $video->getThumbnailHeight());
		Assert::null($video->getVideoPlatform());
	}

}

TestCaseRunner::run(VideoFactoryTest::class);
