<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Media\Exceptions\UnsupportedContentTypeException;
use MichalSpacekCz\Media\Resources\TalkMediaResources;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Http\FileUpload;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class VideoThumbnailsTest extends TestCase
{

	private readonly VideoThumbnails $videoThumbnails;


	public function __construct(
		SupportedImageFileFormats $supportedImageFormats,
	) {
		$mediaResources = new TalkMediaResources('images', 'static', 'location');
		$this->videoThumbnails = new VideoThumbnails($mediaResources, $supportedImageFormats);
	}


	public function testGetUploadedMainFileBasename(): void
	{
		$upload = $this->getFileUpload('foo', UPLOAD_ERR_EXTENSION);
		Assert::null($this->videoThumbnails->getUploadedMainFileBasename($upload));

		$upload = $this->getFileUpload(__DIR__ . '/thumbnail-not-pic', UPLOAD_ERR_OK);
		Assert::exception(function () use ($upload): void {
			$this->videoThumbnails->getUploadedMainFileBasename($upload);
		}, UnsupportedContentTypeException::class, 'Unsupported content type \'text/plain\', available types are {"image/gif":"gif","image/png":"png","image/jpeg":"jpg"}');

		$upload = $this->getFileUpload(__DIR__ . '/thumbnail-gif-no-ext', UPLOAD_ERR_OK);
		Assert::same('video-thumbnail.gif', $this->videoThumbnails->getUploadedMainFileBasename($upload));
	}


	public function testGetUploadedAlternativeFileBasename(): void
	{
		$upload = $this->getFileUpload('foo', UPLOAD_ERR_EXTENSION);
		Assert::null($this->videoThumbnails->getUploadedAlternativeFileBasename($upload));

		$upload = $this->getFileUpload(__DIR__ . '/thumbnail-not-pic', UPLOAD_ERR_OK);
		Assert::exception(function () use ($upload): void {
			$this->videoThumbnails->getUploadedAlternativeFileBasename($upload);
		}, UnsupportedContentTypeException::class, 'Unsupported content type \'text/plain\', available types are {"image/webp":"webp"}');

		$upload = $this->getFileUpload(__DIR__ . '/thumbnail-webp-no-ext', UPLOAD_ERR_OK);
		Assert::same('video-thumbnail.webp', $this->videoThumbnails->getUploadedAlternativeFileBasename($upload));
	}


	private function getFileUpload(string $tmpName, int $error): FileUpload
	{
		return new FileUpload([
			'name' => 'test',
			'size' => 123,
			'tmp_name' => $tmpName,
			'error' => $error,
		]);
	}

}

TestCaseRunner::run(VideoThumbnailsTest::class);
