<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Application\WindowsSubsystemForLinux;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Exceptions\MissingContentTypeException;
use MichalSpacekCz\Media\Resources\TalkMediaResources;
use MichalSpacekCz\Media\SupportedImageFileFormats;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Talks\Exceptions\DuplicatedSlideException;
use MichalSpacekCz\Talks\Exceptions\SlideImageUploadFailedException;
use MichalSpacekCz\Talks\Exceptions\UnknownSlideException;
use MichalSpacekCz\Utils\Base64;
use MichalSpacekCz\Utils\Hash;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\FileUpload;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class TalkSlides
{

	private const SLIDE_MAX_WIDTH = 800;
	private const SLIDE_MAX_HEIGHT = 450;

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
	 * @throws UnknownSlideException
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
				throw new UnknownSlideException($slide, $talkId);
			}
		} elseif (!is_int($slideNo)) {
			throw new ShouldNotHappenException(sprintf("Slide number for slide '%s' of '%s' is a %s not an integer", $slide, $talkId, get_debug_type($slideNo)));
		}
		return $slideNo;
	}


	/**
	 * Get slides for talk.
	 *
	 * @return array<int, Row> slide number => data
	 * @throws ContentTypeException
	 */
	public function getSlides(int $talkId, ?int $filenamesTalkId): array
	{
		$slides = $this->database->fetchAll(
			'SELECT
				id_slide AS slideId,
				alias,
				number,
				filename,
				filename_alternative AS filenameAlternative,
				title,
				speaker_notes AS speakerNotesTexy
			FROM talk_slides
			WHERE key_talk = ?
			ORDER BY number',
			$talkId,
		);

		$filenames = [];
		if ($filenamesTalkId) {
			$result = $this->database->fetchAll(
				'SELECT
       				number,
					filename,
					filename_alternative AS filenameAlternative
				FROM talk_slides
				WHERE key_talk = ?',
				$filenamesTalkId,
			);
			foreach ($result as $row) {
				$filenames[$row->number] = [$row->filename, $row->filenameAlternative];
			}
		}

		$result = [];
		foreach ($slides as $row) {
			if (isset($filenames[$row->number])) {
				$row->filename = $filenames[$row->number][0];
				$row->filenameAlternative = $filenames[$row->number][1];
				$row->filenamesTalkId = $filenamesTalkId;
			} else {
				$row->filenamesTalkId = null;
			}
			$row->speakerNotes = $this->texyFormatter->format($row->speakerNotesTexy);
			$row->image = $row->filename ? $this->talkMediaResources->getImageUrl($filenamesTalkId ?? $talkId, $row->filename) : null;
			$row->imageAlternative = $row->filenameAlternative ? $this->talkMediaResources->getImageUrl($filenamesTalkId ?? $talkId, $row->filenameAlternative) : null;
			$row->imageAlternativeType = ($row->filenameAlternative ? $this->supportedImageFileFormats->getAlternativeContentTypeByExtension(pathinfo($row->filenameAlternative, PATHINFO_EXTENSION)) : null);
			$result[(int)$row->number] = $row;
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
		if (!$contentType) {
			throw new MissingContentTypeException();
		}
		if ($removeFile && !empty($originalFile) && empty($this->otherSlides[$originalFile])) {
			$this->deleteFiles[] = $renamed = $this->talkMediaResources->getImageFilename($talkId, "__del__{$originalFile}");
			rename($this->talkMediaResources->getImageFilename($talkId, $originalFile), $renamed);
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
		if ($imageSize && !($width && $height)) {
			[$width, $height] = $imageSize;
		}
		return "{$name}.{$extension}";
	}


	/**
	 * Insert slides.
	 *
	 * @param int $talkId
	 * @param ArrayHash<ArrayHash<int|string>> $slides
	 * @throws DuplicatedSlideException
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 */
	private function addSlides(int $talkId, ArrayHash $slides): void
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
	 * @param int $talkId
	 * @param array<int, Row> $originalSlides
	 * @param ArrayHash<ArrayHash<int|string>> $slides
	 * @param bool $removeFiles Remove old files?
	 * @throws DuplicatedSlideException
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 */
	private function updateSlides(int $talkId, array $originalSlides, ArrayHash $slides, bool $removeFiles): void
	{
		foreach ($originalSlides as $slide) {
			foreach ([$slide->filename, $slide->filenameAlternative] as $filename) {
				if (!empty($filename)) {
					$this->incrementOtherSlides($filename);
				}
			}
		}
		foreach ($slides as $id => $slide) {
			$width = self::SLIDE_MAX_WIDTH;
			$height = self::SLIDE_MAX_HEIGHT;

			$slideNumber = (int)$slide->number;
			if (isset($slide->replace, $slide->replaceAlternative)) {
				$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImageFileFormats->getMainExtensionByContentType(...), $removeFiles, $originalSlides[$slideNumber]->filename, $width, $height);
				$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedImageFileFormats->getAlternativeExtensionByContentType(...), $removeFiles, $originalSlides[$slideNumber]->filenameAlternative, $width, $height);
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
				$this->updateSlidesRow($talkId, $slide->alias, $slideNumber, $replace ?? $slide->filename ?? '', $replaceAlternative ?? $slide->filenameAlternative ?? '', $slide->title, $slide->speakerNotes, $id);
			} catch (UniqueConstraintViolationException $e) {
				throw new DuplicatedSlideException($slideNumber, previous: $e);
			}
		}
	}


	/**
	 * @throws UniqueConstraintViolationException
	 */
	private function updateSlidesRow(int $talkId, string $alias, int $slideNumber, string $filename, string $filenameAlternative, string $title, string $speakerNotes, mixed $id): void
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
	 * Save new slides.
	 *
	 * @param int $talkId
	 * @param array<int, Row> $originalSlides
	 * @param ArrayHash<int|string> $newSlides
	 * @throws DuplicatedSlideException
	 * @throws ContentTypeException
	 * @throws SlideImageUploadFailedException
	 */
	public function saveSlides(int $talkId, array $originalSlides, ArrayHash $newSlides): void
	{
		$this->database->beginTransaction();
		// Reset slide numbers so they can be shifted around without triggering duplicated key violations
		$this->database->query('UPDATE talk_slides SET number = null WHERE key_talk = ?', $talkId);
		$this->updateSlides($talkId, $originalSlides, $newSlides->slides, $newSlides->deleteReplaced);
		$this->addSlides($talkId, $newSlides->new);
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
		if (!empty($filename) && $this->otherSlides[$filename] > 0) {
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
