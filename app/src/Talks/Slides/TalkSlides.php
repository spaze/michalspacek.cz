<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Slides;

use MichalSpacekCz\Application\WindowsSubsystemForLinux;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Exceptions\MissingContentTypeException;
use MichalSpacekCz\Media\Resources\TalkMediaResources;
use MichalSpacekCz\Media\SupportedImageFileFormats;
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

final class TalkSlides
{

	private const int SLIDE_MAX_WIDTH = 800;
	private const int SLIDE_MAX_HEIGHT = 450;

	/** @var list<string> */
	private array $deleteFiles = [];

	/** @var array<string, int> filename => count */
	private array $otherSlides = [];


	public function __construct(
		private readonly Explorer $database,
		private readonly TypedDatabase $typedDatabase,
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
		$slideNo = $this->typedDatabase->fetchFieldIntNullable('SELECT number FROM talk_slides WHERE key_talk = ? AND alias = ?', $talkId, $slide);
		if ($slideNo === null) {
			if (ctype_digit($slide)) {
				$slideNo = (int)$slide; // To keep deprecated but already existing numerical links (/talk-title/123) working
			} else {
				throw new TalkSlideDoesNotExistException($talkId, $slide);
			}
		}
		return $slideNo;
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getSlides(Talk $talk): TalkSlideCollection
	{
		$slides = $this->typedDatabase->fetchAll(
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
			$result = $this->typedDatabase->fetchAll(
				'SELECT
					number,
					filename,
					filename_alternative AS filenameAlternative
				FROM talk_slides
				WHERE key_talk = ?',
				$talk->getFilenamesTalkId(),
			);
			foreach ($result as $row) {
				assert(is_int($row->number));
				assert(is_string($row->filename));
				assert(is_string($row->filenameAlternative));
				$filenames[$row->number] = [$row->filename, $row->filenameAlternative];
			}
		}

		$result = new TalkSlideCollection($talk->getId());
		foreach ($slides as $row) {
			assert(is_int($row->id));
			assert(is_string($row->alias));
			assert(is_int($row->number));
			assert(is_string($row->filename));
			assert(is_string($row->filenameAlternative));
			assert(is_string($row->title));
			assert(is_string($row->speakerNotesTexy));
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
		if ($removeFile && $originalFile !== null && $this->otherSlides[$originalFile] === 0) {
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
		if ($imageSize !== null && ($width === 0 || $height === 0)) {
			[$width, $height] = $imageSize;
		}
		return "{$name}.{$extension}";
	}


	/**
	 * @throws DuplicatedSlideException
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 */
	private function addSlides(int $talkId, ArrayHash $slides): void
	{
		$lastNumber = 0;
		try {
			foreach ($slides as $slide) {
				assert($slide instanceof ArrayHash);
				assert($slide->replace instanceof FileUpload);
				assert($slide->replaceAlternative instanceof FileUpload);
				assert(is_int($slide->number));
				$width = self::SLIDE_MAX_WIDTH;
				$height = self::SLIDE_MAX_HEIGHT;
				$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImageFileFormats->getMainExtensionByContentType(...), false, null, $width, $height);
				$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedImageFileFormats->getAlternativeExtensionByContentType(...), false, null, $width, $height);
				$lastNumber = $slide->number;
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
	 * @param bool $removeFiles Remove old files?
	 * @throws DuplicatedSlideException
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 * @throws TalkSlideDoesNotExistException
	 */
	private function updateSlides(int $talkId, TalkSlideCollection $originalSlides, ArrayHash $slides, bool $removeFiles): void
	{
		foreach ($originalSlides as $slide) {
			foreach ($slide->getAllFilenames() as $filename) {
				$this->incrementOtherSlides($filename);
			}
		}
		foreach ($slides as $id => $slide) {
			assert($slide instanceof ArrayHash);
			assert($slide->replace instanceof FileUpload || $slide->replace === null);
			assert($slide->replaceAlternative instanceof FileUpload || $slide->replaceAlternative === null);
			assert(is_string($slide->alias));
			assert(is_int($slide->number));
			assert(is_string($slide->filename));
			assert(is_string($slide->filenameAlternative));
			assert(is_string($slide->title));
			assert(is_string($slide->speakerNotes));
			$width = self::SLIDE_MAX_WIDTH;
			$height = self::SLIDE_MAX_HEIGHT;
			$slideFilename = $slide->filename;
			$slideFilenameAlternative = $slide->filenameAlternative;

			if (isset($slide->replace, $slide->replaceAlternative)) {
				$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImageFileFormats->getMainExtensionByContentType(...), $removeFiles, $originalSlides->getByNumber($slide->number)->getFilename(), $width, $height);
				$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedImageFileFormats->getAlternativeExtensionByContentType(...), $removeFiles, $originalSlides->getByNumber($slide->number)->getFilenameAlternative(), $width, $height);
				if ($removeFiles) {
					foreach ($this->deleteFiles as $key => $value) {
						if (unlink($value)) {
							array_splice($this->deleteFiles, $key, 1);
						}
					}
				}
			} else {
				$replace = $replaceAlternative = $slideFilename = $slideFilenameAlternative = null;
			}

			try {
				$this->updateSlidesRow($talkId, $slide->alias, $slide->number, $replace ?? $slideFilename ?? '', $replaceAlternative ?? $slideFilenameAlternative ?? '', $slide->title, $slide->speakerNotes, $id);
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
	 * @throws ContentTypeException
	 * @throws DuplicatedSlideException
	 * @throws SlideImageUploadFailedException
	 * @throws TalkSlideDoesNotExistException
	 */
	public function saveSlides(int $talkId, TalkSlideCollection $originalSlides, ArrayHash $updateSlides, ArrayHash $newSlides, bool $deleteReplaced): void
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
