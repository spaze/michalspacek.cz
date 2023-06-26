<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use MichalSpacekCz\Application\WindowsSubsystemForLinux;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Resources\TalkMediaResources;
use MichalSpacekCz\Media\SupportedImageFileFormats;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Talks\Exceptions\DuplicatedSlideException;
use MichalSpacekCz\Utils\Base64;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\FileUpload;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Json;
use RuntimeException;

class Talks
{

	private const SLIDE_MAX_WIDTH = 800;
	private const SLIDE_MAX_HEIGHT = 450;

	/** @var string[] */
	private array $deleteFiles = [];

	/** @var int[] */
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
	 * Get all talks, or almost all talks.
	 *
	 * @param int|null $limit
	 * @return Row[]
	 */
	public function getAll(?int $limit = null): array
	{
		$query = 'SELECT
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.date,
				t.duration,
				t.href,
				t.slides_href IS NOT NULL OR EXISTS (SELECT * FROM talk_slides s WHERE s.key_talk = COALESCE(t.key_talk_slides, t.id_talk)) AS hasSlides,
				t.video_href AS videoHref,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref
			FROM talks t
			WHERE t.date <= NOW()
			ORDER BY t.date DESC
			LIMIT ?';

		$result = $this->database->fetchAll($query, $limit ?? PHP_INT_MAX);
		foreach ($result as $row) {
			$this->enrich($row);
		}

		return $result;
	}


	/**
	 * Get approximate number of talks.
	 *
	 * @return int
	 */
	public function getApproxCount(): int
	{
		$count = $this->database->fetchField('SELECT COUNT(*) FROM talks WHERE date <= NOW()');
		return (int)($count / 10) * 10;
	}


	/**
	 * Get upcoming talks.
	 *
	 * @return Row[]
	 */
	public function getUpcoming(): array
	{
		$query = 'SELECT
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.date,
				t.duration,
				t.href,
				t.slides_href IS NOT NULL OR EXISTS (SELECT * FROM talk_slides s WHERE s.key_talk = COALESCE(t.key_talk_slides, t.id_talk)) AS hasSlides,
				t.video_href AS videoHref,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref
			FROM talks t
			WHERE t.date > NOW()
			ORDER BY t.date';

		$result = $this->database->fetchAll($query);
		foreach ($result as $row) {
			$this->enrich($row);
		}

		return $result;
	}


	/**
	 * Get talk data.
	 *
	 * @param string $name
	 * @return Row<mixed>
	 */
	public function get(string $name): Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.description,
				t.description AS descriptionTexy,
				t.date,
				t.duration,
				t.href,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_thumbnail AS videoThumbnail,
				t.video_thumbnail_alternative AS videoThumbnailAlternative,
				t.video_embed AS videoEmbed,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.transcript AS transcriptTexy,
				t.favorite,
				t.key_talk_slides AS slidesTalkId,
				t.key_talk_filenames AS filenamesTalkId,
				t.key_superseded_by AS supersededById,
				ts.action AS supersededByAction,
				ts.title AS supersededByTitle,
				t.publish_slides AS publishSlides
			FROM talks t
				LEFT JOIN talks ts ON t.key_superseded_by = ts.id_talk
			WHERE t.action = ?',
			$name,
		);

		if (!$result) {
			throw new RuntimeException("I haven't talked about {$name}, yet");
		}

		$this->enrich($result);
		return $result;
	}


	/**
	 * Get talk data by id.
	 *
	 * @param int $id
	 * @return Row<mixed>
	 */
	public function getById(int $id): Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch(
			'SELECT
				t.id_talk AS talkId,
				t.action,
				t.title,
				t.title AS titleTexy,
				t.description,
				t.description AS descriptionTexy,
				t.date,
				t.duration,
				t.href,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_thumbnail AS videoThumbnail,
				t.video_thumbnail_alternative AS videoThumbnailAlternative,
				t.video_embed AS videoEmbed,
				t.event,
				t.event AS eventTexy,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.transcript AS transcriptTexy,
				t.favorite,
				t.key_talk_slides AS slidesTalkId,
				t.key_talk_filenames AS filenamesTalkId,
				t.key_superseded_by AS supersededById,
				ts.action AS supersededByAction,
				ts.title AS supersededByTitle,
				t.publish_slides AS publishSlides
			FROM talks t
				LEFT JOIN talks ts ON t.key_superseded_by = ts.id_talk
			WHERE t.id_talk = ?',
			$id,
		);

		if (!$result) {
			throw new RuntimeException("I haven't talked about id {$id}, yet");
		}

		$this->enrich($result);
		return $result;
	}


	/**
	 * @param Row<mixed> $row
	 */
	private function enrich(Row $row): void
	{
		$this->texyFormatter->setTopHeading(3);
		foreach (['title', 'event'] as $item) {
			if (isset($row[$item])) {
				$row[$item] = $this->texyFormatter->format($row[$item]);
			}
		}
		foreach (['description', 'transcript'] as $item) {
			if (isset($row[$item])) {
				$row[$item] = $this->texyFormatter->formatBlock($row[$item]);
			}
		}
		$row->videoThumbnailUrl = isset($row->videoThumbnail) ? $this->talkMediaResources->getImageUrl($row->talkId, $row->videoThumbnail) : null;
		$row->videoThumbnailAlternativeUrl = isset($row->videoThumbnailAlternative) ? $this->talkMediaResources->getImageUrl($row->talkId, $row->videoThumbnailAlternative) : null;
	}


	/**
	 * Get favorite talks.
	 *
	 * @return Html[]
	 */
	public function getFavorites(): array
	{
		$query = 'SELECT
				action,
				title,
				favorite
			FROM talks
			WHERE favorite IS NOT NULL
			ORDER BY date DESC';

		$result = [];
		foreach ($this->database->fetchAll($query) as $row) {
			$result[] = $this->texyFormatter->substitute($row['favorite'], [$row['title'], $row['action']]);
		}

		return $result;
	}


	/**
	 * Return slide number by given alias.
	 *
	 * @param int $talkId
	 * @param string|null $slide
	 * @return int|null Slide number or null if no slide given, or slide not found
	 */
	public function getSlideNo(int $talkId, ?string $slide): ?int
	{
		if ($slide === null) {
			return null;
		}
		$slideNo = $this->database->fetchField('SELECT number FROM talk_slides WHERE key_talk = ? AND alias = ?', $talkId, $slide);
		if ($slideNo === false) {
			if (ctype_digit($slide)) {
				$slideNo = (int)$slide; // Too keep deprecated but already existing numerical links (/talk-title/123) working
			} else {
				throw new RuntimeException("Unknown slide {$slide} for talk {$talkId}");
			}
		} elseif (!is_int($slideNo)) {
			throw new ShouldNotHappenException(sprintf("Slide number for slide '%s' of '%s' is a %s not an integer", $slide, $talkId, get_debug_type($slideNo)));
		}
		return $slideNo;
	}


	/**
	 * Update talk data.
	 */
	public function update(
		int $id,
		?string $action,
		string $title,
		?string $description,
		string $date,
		?int $duration,
		?string $href,
		?int $slidesTalk,
		?int $filenamesTalk,
		?string $slidesHref,
		?string $slidesEmbed,
		?string $videoHref,
		?string $videoThumbnail,
		?string $videoThumbnailAlternative,
		?string $videoEmbed,
		string $event,
		?string $eventHref,
		?string $ogImage,
		?string $transcript,
		?string $favorite,
		?int $supersededBy,
		bool $publishSlides,
	): void {
		$this->database->query(
			'UPDATE talks SET ? WHERE id_talk = ?',
			[
				'action' => (empty($action) ? null : $action),
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new DateTime($date),
				'duration' => (empty($duration) ? null : $duration),
				'href' => (empty($href) ? null : $href),
				'key_talk_slides' => (empty($slidesTalk) ? null : $slidesTalk),
				'key_talk_filenames' => (empty($filenamesTalk) ? null : $filenamesTalk),
				'slides_href' => (empty($slidesHref) ? null : $slidesHref),
				'slides_embed' => (empty($slidesEmbed) ? null : $slidesEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_thumbnail' => $videoThumbnail,
				'video_thumbnail_alternative' => $videoThumbnailAlternative,
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'event' => $event,
				'event_href' => (empty($eventHref) ? null : $eventHref),
				'og_image' => (empty($ogImage) ? null : $ogImage),
				'transcript' => (empty($transcript) ? null : $transcript),
				'favorite' => (empty($favorite) ? null : $favorite),
				'key_superseded_by' => (empty($supersededBy) ? null : $supersededBy),
				'publish_slides' => $publishSlides,
			],
			$id,
		);
	}


	/**
	 * Insert talk data.
	 */
	public function add(
		?string $action,
		string $title,
		?string $description,
		string $date,
		?int $duration,
		?string $href,
		?int $slidesTalk,
		?int $filenamesTalk,
		?string $slidesHref,
		?string $slidesEmbed,
		?string $videoHref,
		?string $videoThumbnail,
		?string $videoThumbnailAlternative,
		?string $videoEmbed,
		string $event,
		?string $eventHref,
		?string $ogImage,
		?string $transcript,
		?string $favorite,
		?int $supersededBy,
		bool $publishSlides,
	): int {
		$this->database->query(
			'INSERT INTO talks',
			[
				'action' => (empty($action) ? null : $action),
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new DateTime($date),
				'duration' => (empty($duration) ? null : $duration),
				'href' => (empty($href) ? null : $href),
				'key_talk_slides' => (empty($slidesTalk) ? null : $slidesTalk),
				'key_talk_filenames' => (empty($filenamesTalk) ? null : $filenamesTalk),
				'slides_href' => (empty($slidesHref) ? null : $slidesHref),
				'slides_embed' => (empty($slidesEmbed) ? null : $slidesEmbed),
				'video_href' => (empty($videoHref) ? null : $videoHref),
				'video_thumbnail' => $videoThumbnail,
				'video_thumbnail_alternative' => $videoThumbnailAlternative,
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'event' => $event,
				'event_href' => (empty($eventHref) ? null : $eventHref),
				'og_image' => (empty($ogImage) ? null : $ogImage),
				'transcript' => (empty($transcript) ? null : $transcript),
				'favorite' => (empty($favorite) ? null : $favorite),
				'key_superseded_by' => (empty($supersededBy) ? null : $supersededBy),
				'publish_slides' => $publishSlides,
			],
		);
		return (int)$this->database->getInsertId();
	}


	/**
	 * Get slides for talk.
	 *
	 * @param int $talkId Talk id
	 * @param int|null $filenamesTalkId
	 * @return Row[]
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
			$result[$row->number] = $row;
		}
		return $result;
	}


	private function getSlideImageFileBasename(string $contents): string
	{
		return Base64::urlEncode(sha1($contents, true));
	}


	/**
	 * @param int $talkId
	 * @param FileUpload $replace
	 * @param callable(string): string $getExtension
	 * @param bool $removeFile
	 * @param string|null $originalFile
	 * @param int $width
	 * @param int $height
	 * @return null|string
	 * @throws ContentTypeException
	 */
	private function replaceSlideImage(int $talkId, FileUpload $replace, callable $getExtension, bool $removeFile, ?string $originalFile, int &$width, int &$height): ?string
	{
		if (!$replace->hasFile()) {
			return null;
		}
		$contents = $replace->getContents();
		if ($contents === null) {
			throw new RuntimeException('Slide image upload failed', $replace->getError());
		}
		$contentType = $replace->getContentType();
		if (!$contentType) {
			throw new ContentTypeException();
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
	 * @param Row[] $originalSlides
	 * @param ArrayHash<ArrayHash<int|string>> $slides
	 * @param bool $removeFiles Remove old files?
	 * @throws DuplicatedSlideException
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
		try {
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

				$this->updateSlidesRow($talkId, $slide->alias, $slideNumber, $replace ?? $slide->filename ?? '', $replaceAlternative ?? $slide->filenameAlternative ?? '', $slide->title, $slide->speakerNotes, $id);
			}
		} catch (UniqueConstraintViolationException $e) {
			throw new DuplicatedSlideException($slideNumber, previous: $e);
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
	 * @param Row[] $originalSlides
	 * @param ArrayHash<int|string> $newSlides
	 * @throws DuplicatedSlideException
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


	/**
	 * Build page title for the talk.
	 *
	 * @param string $translationKey
	 * @param Row<mixed> $talk
	 * @return Html<Html|string>
	 */
	public function pageTitle(string $translationKey, Row $talk): Html
	{
		return $this->texyFormatter->translate($translationKey, [strip_tags((string)$talk->title), $talk->event]);
	}


	/**
	 * Increment other slides count.
	 *
	 * @param string $filename
	 */
	private function incrementOtherSlides(string $filename): void
	{
		if (isset($this->otherSlides[$filename])) {
			$this->otherSlides[$filename]++;
		} else {
			$this->otherSlides[$filename] = 0;
		}
	}


	/**
	 * Increment other slides count.
	 *
	 * @param string|null $filename
	 */
	private function decrementOtherSlides(?string $filename): void
	{
		if (!empty($filename) && $this->otherSlides[$filename] > 0) {
			$this->otherSlides[$filename]--;
		}
	}


	/**
	 * Get max slide dimensions and aspect ratio as JSON string.
	 *
	 * @return string
	 */
	public function getSlideDimensions(): string
	{
		return Json::encode([
			'ratio' => ['width' => 16, 'height' => 9],
			'max' => ['width' => self::SLIDE_MAX_WIDTH, 'height' => self::SLIDE_MAX_HEIGHT],
		]);
	}

}
