<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use MichalSpacekCz\Application\WindowsSubsystemForLinux;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Exceptions\MissingContentTypeException;
use MichalSpacekCz\Media\Resources\TalkMediaResources;
use MichalSpacekCz\Media\SupportedImageFileFormats;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Talks\Exceptions\DuplicatedSlideException;
use MichalSpacekCz\Talks\Exceptions\SlideImageUploadFailedException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideDoesNotExistException;
use MichalSpacekCz\Talks\Talk;
use MichalSpacekCz\Utils\Base64;
use MichalSpacekCz\Utils\Hash;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\FileUpload;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class TalkSlides
{

	private const int SLIDE_MAX_WIDTH = 800;
	private const int SLIDE_MAX_HEIGHT = 450;

	/** @var list<string> */
	private array $deleteFiles = [];

	/** @var array<string, int> filename => count */
	private array $otherSlides = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly TexyFormatter $texyFormatter,
		private readonly TalkMediaResources $talkMediaResources,
		private readonly SupportedImageFileFormats $supportedImageFileFormats,
		private readonly WindowsSubsystemForLinux $windowsSubsystemForLinux,
	) {
	}


	/**
	 * Return slide number by given alias.
	 *
	 * @throws TalkSlideDoesNotExistException
	 */
	public function getSlideNo(int $talkId, ?string $slide): ?int
	{
		if ($slide === null) {
			return null;
		}
		$slideNo = $this->database->fetchField('SELECT number FROM talk_slides WHERE key_talk = ? AND alias = ?', $talkId, $slide);
		if (!$slideNo) {
			if (ctype_digit($slide)) {
				$slideNo = (int)$slide; // To keep deprecated but already existing numerical links (/talk-title/123) working
			} else {
				throw new TalkSlideDoesNotExistException($talkId, $slide);
			}
		} elseif (!is_int($slideNo)) {
			throw new ShouldNotHappenException(sprintf("Slide number for slide '%s' of '%s' is a %s not an integer", $slide, $talkId, get_debug_type($slideNo)));
		}
		return $slideNo;
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getSlides(Talk $talk): TalkSlideCollection
	{
		$slides = $this->database->fetchAll(
			'SELECT
				id_slide AS id,
				alias,
				number,
				filename,
				filename_alternative AS filenameAlternative,
				title,
				speaker_notes AS speakerNotesTexy
			FROM talk_slides
			WHERE key_talk = ?
			ORDER BY number',
			$talk->getId(),
		);

		$filenames = [];
		if ($talk->getFilenamesTalkId() !== null) {
			$result = $this->database->fetchAll(
				'SELECT
					number,
					filename,
					filename_alternative AS filenameAlternative
				FROM talk_slides
				WHERE key_talk = ?',
				$talk->getFilenamesTalkId(),
			);
			foreach ($result as $row) {
				$filenames[$row->number] = [$row->filename, $row->filenameAlternative];
			}
		}

		$result = new TalkSlideCollection($talk->getId());
		foreach ($slides as $row) {
			if (isset($filenames[$row->number])) {
				$filename = $filenames[$row->number][0];
				$filenameAlternative = $filenames[$row->number][1];
				$filenamesTalkId = $talk->getFilenamesTalkId();
			} else {
				$filename = $row->filename;
				$filenameAlternative = $row->filenameAlternative;
				$filenamesTalkId = null;
			}
			if ($filename === '') {
				$filename = null;
			}
			if ($filenameAlternative === '') {
				$filenameAlternative = null;
			}
			$slide = new TalkSlide(
				$row->id,
				$row->alias,
				$row->number,
				$filename,
				$filenameAlternative,
				$filenamesTalkId,
				$row->title,
				$this->texyFormatter->format($row->speakerNotesTexy),
				$row->speakerNotesTexy,
				$filename !== null ? $this->talkMediaResources->getImageUrl($filenamesTalkId ?? $talk->getId(), $filename) : null,
				$filenameAlternative !== null ? $this->talkMediaResources->getImageUrl($filenamesTalkId ?? $talk->getId(), $filenameAlternative) : null,
				$filenameAlternative !== null ? $this->supportedImageFileFormats->getAlternativeContentTypeByExtension(pathinfo($filenameAlternative, PATHINFO_EXTENSION)) : null,
			);
			$result->add($slide);
		}
		return $result;
	}


	private function getSlideImageFileBasename(string $contents): string
	{
		return Base64::urlEncode(Hash::nonCryptographic($contents, true));
	}


	/**
	 * @param callable(string): string $getExtension
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 */
	private function replaceSlideImage(int $talkId, FileUpload $replace, callable $getExtension, bool $removeFile, ?string $originalFile, int &$width, int &$height): ?string
	{
		if (!$replace->hasFile()) {
			return null;
		}
		$contents = $replace->getContents();
		if ($contents === null) {
			throw new SlideImageUploadFailedException($replace->getError());
		}
		$contentType = $replace->getContentType();
		if ($contentType === null) {
			throw new MissingContentTypeException();
		}
		if ($removeFile && $originalFile !== null && empty($this->otherSlides[$originalFile])) {
			$imageFilename = $this->talkMediaResources->getImageFilename($talkId, $originalFile);
			if (file_exists($imageFilename)) {
				$this->deleteFiles[] = $renamed = $this->talkMediaResources->getImageFilename($talkId, "__del__{$originalFile}");
				rename($imageFilename, $renamed);
			}
		}
		$name = $this->getSlideImageFileBasename($contents);
		$extension = $getExtension($contentType);
		$imageSize = $replace->getImageSize();
		try {
			$replace->move($this->talkMediaResources->getImageFilename($talkId, "{$name}.{$extension}"));
		} catch (InvalidStateException $e) {
			if (!$this->windowsSubsystemForLinux->isWsl()) {
				throw $e;
			}
		}
		$this->decrementOtherSlides($originalFile);
		$this->incrementOtherSlides("{$name}.{$extension}");
		if ($imageSize !== null && !($width && $height)) {
			[$width, $height] = $imageSize;
		}
		return "{$name}.{$extension}";
	}


	/**
	 * Insert slides.
	 *
	 * @param int $talkId
	 * @param list<ArrayHash<int|string|FileUpload|null>> $slides
	 * @throws DuplicatedSlideException
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 */
	private function addSlides(int $talkId, array $slides): void
	{
		$lastNumber = 0;
		try {
			foreach ($slides as $slide) {
				$width = self::SLIDE_MAX_WIDTH;
				$height = self::SLIDE_MAX_HEIGHT;
				$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImageFileFormats->getMainExtensionByContentType(...), false, null, $width, $height);
				$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedImageFileFormats->getAlternativeExtensionByContentType(...), false, null, $width, $height);
				$lastNumber = (int)$slide->number;
				$this->database->query(
					'INSERT INTO talk_slides',
					[
						'key_talk' => $talkId,
						'alias' => $slide->alias,
						'number' => $slide->number,
						'filename' => $replace ?? $slide->filename ?? '',
						'filename_alternative' => $replaceAlternative ?? $slide->filenameAlternative ?? '',
						'title' => $slide->title,
						'speaker_notes' => $slide->speakerNotes,
					],
				);
			}
		} catch (UniqueConstraintViolationException $e) {
			throw new DuplicatedSlideException($lastNumber, previous: $e);
		}
	}


	/**
	 * Update slides.
	 *
	 * @param array<int, ArrayHash<int|string|FileUpload|null>> $slides
	 * @param bool $removeFiles Remove old files?
	 * @throws DuplicatedSlideException
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 * @throws TalkSlideDoesNotExistException
	 */
	private function updateSlides(int $talkId, TalkSlideCollection $originalSlides, array $slides, bool $removeFiles): void
	{
		foreach ($originalSlides as $slide) {
			foreach ($slide->getAllFilenames() as $filename) {
				$this->incrementOtherSlides($filename);
			}
		}
		foreach ($slides as $id => $slide) {
			$width = self::SLIDE_MAX_WIDTH;
			$height = self::SLIDE_MAX_HEIGHT;

			if (isset($slide->replace, $slide->replaceAlternative)) {
				$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImageFileFormats->getMainExtensionByContentType(...), $removeFiles, $originalSlides->getByNumber($slide->number)->getFilename(), $width, $height);
				$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedImageFileFormats->getAlternativeExtensionByContentType(...), $removeFiles, $originalSlides->getByNumber($slide->number)->getFilenameAlternative(), $width, $height);
				if ($removeFiles) {
					foreach ($this->deleteFiles as $key => $value) {
						if (unlink($value)) {
							unset($this->deleteFiles[$key]);
						}
					}
				}
			} else {
				$replace = $replaceAlternative = $slide->filename = $slide->filenameAlternative = null;
			}

			try {
				$this->updateSlidesRow($talkId, $slide->alias, $slide->number, $replace ?? $slide->filename ?? '', $replaceAlternative ?? $slide->filenameAlternative ?? '', $slide->title, $slide->speakerNotes, $id);
			} catch (UniqueConstraintViolationException $e) {
				throw new DuplicatedSlideException($slide->number, previous: $e);
			}
		}
	}


	/**
	 * @throws UniqueConstraintViolationException
	 */
	private function updateSlidesRow(int $talkId, string $alias, int $slideNumber, string $filename, string $filenameAlternative, string $title, string $speakerNotes, int $id): void
	{
		$this->database->query(
			'UPDATE talk_slides SET ? WHERE id_slide = ?',
			[
				'key_talk' => $talkId,
				'alias' => $alias,
				'number' => $slideNumber,
				'filename' => $filename,
				'filename_alternative' => $filenameAlternative,
				'title' => $title,
				'speaker_notes' => $speakerNotes,
			],
			$id,
		);
	}


	/**
	 * @param array<int, ArrayHash<int|string|FileUpload|null>> $updateSlides
	 * @param list<ArrayHash<int|string|FileUpload|null>> $newSlides
	 * @throws ContentTypeException
	 * @throws DuplicatedSlideException
	 * @throws SlideImageUploadFailedException
	 * @throws TalkSlideDoesNotExistException
	 */
	public function saveSlides(int $talkId, TalkSlideCollection $originalSlides, array $updateSlides, array $newSlides, bool $deleteReplaced): void
	{
		$this->otherSlides = [];
		$this->database->beginTransaction();
		// Reset slide numbers so they can be shifted around without triggering duplicated key violations
		$this->database->query('UPDATE talk_slides SET number = null WHERE key_talk = ?', $talkId);
		$this->updateSlides($talkId, $originalSlides, $updateSlides, $deleteReplaced);
		$this->addSlides($talkId, $newSlides);
		$this->database->commit();
	}


	private function incrementOtherSlides(string $filename): void
	{
		if (isset($this->otherSlides[$filename])) {
			$this->otherSlides[$filename]++;
		} else {
			$this->otherSlides[$filename] = 0;
		}
	}


	private function decrementOtherSlides(?string $filename): void
	{
		if ($filename !== null && $this->otherSlides[$filename] > 0) {
			$this->otherSlides[$filename]--;
		}
	}


	/**
	 * Get max slide dimensions and aspect ratio as JSON string.
	 *
	 * @throws JsonException
	 */
	public function getSlideDimensions(): string
	{
		return Json::encode([
			'ratio' => ['width' => 16, 'height' => 9],
			'max' => ['width' => self::SLIDE_MAX_WIDTH, 'height' => self::SLIDE_MAX_HEIGHT],
		]);
	}

}
