<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Media\Resources\InterviewMediaResources;
use Nette\Database\Row;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class VideoFactoryTest extends TestCase
{

	private readonly VideoFactory $videoFactory;
	private readonly VideoThumbnails $videoThumbnails;


	public function __construct(
		private readonly InterviewMediaResources $mediaResources,
		private readonly SupportedImageFileFormats $supportedImageFileFormats,
	) {
		$this->videoThumbnails = new VideoThumbnails($this->mediaResources, $this->supportedImageFileFormats);
		$this->videoFactory = new VideoFactory($this->mediaResources, $this->supportedImageFileFormats, $this->videoThumbnails);
	}


	public function testCreateFromDatabaseRow(): void
	{
		$row = Row::from([
			'id' => 123,
			'videoHref' => 'https://youtube.com/foo',
			'videoThumbnail' => 'thumb.jpg',
			'videoThumbnailAlternative' => 'thumb.webp',
		]);
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

		$row = Row::from([
			'id' => 123,
			'videoHref' => null,
			'videoThumbnail' => null,
			'videoThumbnailAlternative' => null,
		]);
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

$runner->run(VideoFactoryTest::class);
