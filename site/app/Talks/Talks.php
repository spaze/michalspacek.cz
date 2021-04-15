<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use MichalSpacekCz\Formatter\Texy;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Json;
use PDOException;
use RuntimeException;
use UnexpectedValueException;

class Talks
{

	/** @var integer */
	private const SLIDE_MAX_WIDTH = 800;

	/** @var integer */
	private const SLIDE_MAX_HEIGHT = 450;

	private Explorer $database;

	private Texy $texyFormatter;

	/**
	 * Slides root, just directory no FQND, no leading slash, no trailing slash.
	 */
	private string $slidesRoot;

	/**
	 * Static files root FQDN, no trailing slash.
	 */
	private string $staticRoot;

	/**
	 * Physical location root directory, no trailing slash.
	 */
	private string $locationRoot;

	/** @var string[] */
	private array $deleteFiles = [];

	/** @var integer[] */
	private array $otherSlides = [];

	/** @var string[] */
	private array $supportedImages = [
		'image/gif' => 'gif',
		'image/png' => 'png',
		'image/jpeg' => 'jpg',
	];

	/** @var string[] */
	private array $supportedAlternativeImages = [
		'image/webp' => 'webp',
	];


	public function __construct(Explorer $context, Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Set static content URL root.
	 *
	 * @param string $root
	 */
	public function setStaticRoot($root): void
	{
		$this->staticRoot = rtrim($root, '/');
	}


	/**
	 * Set location root directory.
	 *
	 * @param string $root
	 */
	public function setLocationRoot($root): void
	{
		$this->locationRoot = rtrim($root, '/');
	}


	/**
	 * Set slides root directory.
	 *
	 * Removes both leading and trailing forward slashes.
	 *
	 * @param string $root
	 * @param string $slidesRoot
	 */
	public function setSlidesRoot(string $root, string $slidesRoot): void
	{
		$this->slidesRoot = trim($root, '/') . '/' . trim($slidesRoot, '/');
	}


	/**
	 * Get all talks, or almost all talks.
	 *
	 * @param integer|null $limit
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
			ORDER BY t.date DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getDriver()->applyLimit($query, $limit, null);
		}

		$result = $this->database->fetchAll($query);
		foreach ($result as $row) {
			$this->format($row);
		}

		return $result;
	}


	/**
	 * Get approximate number of talks.
	 *
	 * @return integer
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
			$this->format($row);
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
			$name
		);

		if (!$result) {
			throw new RuntimeException("I haven't talked about {$name}, yet");
		}

		$this->format($result);
		return $result;
	}


	/**
	 * Get talk data by id.
	 *
	 * @param integer $id
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
			$id
		);

		if (!$result) {
			throw new RuntimeException("I haven't talked about id {$id}, yet");
		}

		$this->format($result);
		return $result;
	}


	/**
	 * @param Row<mixed> $row
	 */
	private function format(Row $row): void
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

		$result = array();
		foreach ($this->database->fetchAll($query) as $row) {
			$result[] = $this->texyFormatter->substitute($row['favorite'], [$row['title'], $row['action']]);
		}

		return $result;
	}


	/**
	 * Return slide number by given alias.
	 *
	 * @param integer $talkId
	 * @param string|null $slide
	 * @return integer|null Slide number or null if no slide given, or slide not found
	 */
	public function getSlideNo(int $talkId, ?string $slide): ?int
	{
		if ($slide === null) {
			return null;
		}
		$slideNo = $this->database->fetchField('SELECT number FROM talk_slides WHERE key_talk = ? AND alias = ?', $talkId, $slide);
		if ($slideNo === false) {
			if (ctype_digit($slide)) {
				$slideNo = (int)$slide;  // Too keep deprecated but already existing numerical links (/talk-title/123) working
			} else {
				throw new RuntimeException("Unknown slide {$slide} for talk {$talkId}");
			}
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
		?string $videoEmbed,
		string $event,
		?string $eventHref,
		?string $ogImage,
		?string $transcript,
		?string $favorite,
		?int $supersededBy,
		bool $publishSlides
	): void {
		$this->database->query(
			'UPDATE talks SET ? WHERE id_talk = ?',
			array(
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
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'event' => $event,
				'event_href' => (empty($eventHref) ? null : $eventHref),
				'og_image' => (empty($ogImage) ? null : $ogImage),
				'transcript' => (empty($transcript) ? null : $transcript),
				'favorite' => (empty($favorite) ? null : $favorite),
				'key_superseded_by' => (empty($supersededBy) ? null : $supersededBy),
				'publish_slides' => $publishSlides,
			),
			$id
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
		?string $videoEmbed,
		string $event,
		?string $eventHref,
		?string $ogImage,
		?string $transcript,
		?string $favorite,
		?int $supersededBy,
		bool $publishSlides
	): void {
		$this->database->query(
			'INSERT INTO talks',
			array(
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
				'video_embed' => (empty($videoEmbed) ? null : $videoEmbed),
				'event' => $event,
				'event_href' => (empty($eventHref) ? null : $eventHref),
				'og_image' => (empty($ogImage) ? null : $ogImage),
				'transcript' => (empty($transcript) ? null : $transcript),
				'favorite' => (empty($favorite) ? null : $favorite),
				'key_superseded_by' => (empty($supersededBy) ? null : $supersededBy),
				'publish_slides' => $publishSlides,
			)
		);
	}


	/**
	 * Get slides for talk.
	 *
	 * @param integer $talkId Talk id
	 * @param integer|null $filenamesTalkId
	 * @return Row[]
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
			$talkId
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
				$filenamesTalkId
			);
			foreach ($result as $row) {
				$filenames[$row->number] = [$row->filename, $row->filenameAlternative];
			}
		}

		$result = [];
		$alternativeTypes = array_flip($this->supportedAlternativeImages);
		foreach ($slides as $row) {
			if (isset($filenames[$row->number])) {
				$row->filename = $filenames[$row->number][0];
				$row->filenameAlternative = $filenames[$row->number][1];
				$row->filenamesTalkId = $filenamesTalkId;
			} else {
				$row->filenamesTalkId = null;
			}
			$row->speakerNotes = $this->texyFormatter->format($row->speakerNotesTexy);
			$row->image = $this->getSlideImageFilename($this->staticRoot, $filenamesTalkId ?? $talkId, $row->filename);
			$row->imageAlternative = $this->getSlideImageFilename($this->staticRoot, $filenamesTalkId ?? $talkId, $row->filenameAlternative);
			$row->imageAlternativeType = ($row->filenameAlternative ? $alternativeTypes[pathinfo($row->filenameAlternative, PATHINFO_EXTENSION)] : null);
			$result[$row->number] = $row;
		}
		return $result;
	}


	/**
	 * @param string $prefix
	 * @param integer $talkId
	 * @param string $filename
	 * @return null|string
	 */
	private function getSlideImageFilename(string $prefix, int $talkId, string $filename): ?string
	{
		return (empty($filename) ? null : "{$prefix}/{$this->slidesRoot}/{$talkId}/{$filename}");
	}


	/**
	 * @param integer $talkId
	 * @param FileUpload $replace
	 * @param string[] $supported
	 * @param boolean $removeFile
	 * @param string|null $originalFile
	 * @param integer $width
	 * @param integer $height
	 * @return null|string
	 */
	private function replaceSlideImage(int $talkId, FileUpload $replace, array $supported, bool $removeFile, ?string $originalFile, int &$width, int &$height): ?string
	{
		if (!$replace->hasFile()) {
			return null;
		}
		if (!$replace->isOk()) {
			throw new RuntimeException('Slide image upload failed', $replace->getError());
		}
		if (!in_array($replace->getContentType(), array_keys($supported))) {
			throw new RuntimeException('Slide image type not allowed: ' . $replace->getContentType());
		}
		if ($removeFile && !empty($originalFile) && empty($this->otherSlides[$originalFile])) {
			$this->deleteFiles[] = $renamed = $this->getSlideImageFilename($this->locationRoot, $talkId, "__del__{$originalFile}");
			rename($this->getSlideImageFilename($this->locationRoot, $talkId, $originalFile), $renamed);
		}
		$name = strtr(rtrim(base64_encode(sha1($replace->getContents(), true)), '='), '+/', '-_');
		$extension = $supported[$replace->getContentType()];
		$replace->move($this->getSlideImageFilename($this->locationRoot, $talkId, "{$name}.{$extension}"));
		$this->decrementOtherSlides($originalFile);
		$this->incrementOtherSlides("{$name}.{$extension}");
		if (!$width || !$height) {
			list($width, $height) = $replace->getImageSize();
		}
		return "{$name}.{$extension}";
	}


	/**
	 * Insert slides.
	 *
	 * @param integer $talkId
	 * @param ArrayHash<ArrayHash<integer|string>> $slides
	 * @throws UnexpectedValueException on duplicate entry (key_talk, number)
	 * @throws PDOException
	 */
	private function addSlides(int $talkId, ArrayHash $slides): void
	{
		$lastNumber = 0;
		try {
			foreach ($slides as $slide) {
				$width = self::SLIDE_MAX_WIDTH;
				$height = self::SLIDE_MAX_HEIGHT;
				$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImages, false, null, $width, $height);
				$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedAlternativeImages, false, null, $width, $height);
				$lastNumber = (int)$slide->number;
				$this->database->query(
					'INSERT INTO talk_slides',
					array(
						'key_talk' => $talkId,
						'alias' => $slide->alias,
						'number' => $slide->number,
						'filename' => $replace ?: $slide->filename,
						'filename_alternative' => $replaceAlternative ?: $slide->filenameAlternative,
						'title' => $slide->title,
						'speaker_notes' => $slide->speakerNotes,
					)
				);
			}
		} catch (PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == MySqlDriver::ERROR_DUPLICATE_ENTRY) {
					throw new UnexpectedValueException($e->getMessage(), $lastNumber);
				}
			}
			throw $e;
		}
	}


	/**
	 * Update slides.
	 *
	 * @param integer $talkId
	 * @param Row[] $originalSlides
	 * @param ArrayHash<ArrayHash<integer|string>> $slides
	 * @param boolean $removeFiles Remove old files?
	 * @throws UnexpectedValueException on duplicate entry (key_talk, number)
	 * @throws PDOException
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
					$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImages, $removeFiles, $originalSlides[$slideNumber]->filename, $width, $height);
					$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedAlternativeImages, $removeFiles, $originalSlides[$slideNumber]->filenameAlternative, $width, $height);
					if ($removeFiles) {
						// TODO delete renamed files
					}
				} else {
					$replace = $replaceAlternative = $slide->filename = $slide->filenameAlternative = null;
				}

				$this->database->query(
					'UPDATE talk_slides SET ? WHERE id_slide = ?',
					array(
						'key_talk' => $talkId,
						'alias' => $slide->alias,
						'number' => $slideNumber,
						'filename' => $replace ?: $slide->filename,
						'filename_alternative' => $replaceAlternative ?: $slide->filenameAlternative,
						'title' => $slide->title,
						'speaker_notes' => $slide->speakerNotes,
					),
					$id
				);
			}
		} catch (PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == MySqlDriver::ERROR_DUPLICATE_ENTRY) {
					throw new UnexpectedValueException($e->getMessage(), $slideNumber);
				}
			}
			throw $e;
		}
	}


	/**
	 * Save new slides.
	 *
	 * @param integer $talkId
	 * @param Row[] $originalSlides
	 * @param ArrayHash<integer|string> $newSlides
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
	 * Get supported image types.
	 *
	 * @return string[] MIME type => extension
	 */
	public function getSupportedImages()
	{
		return $this->supportedImages;
	}


	/**
	 * Get supported alternative image types.
	 *
	 * @return string[] MIME type => extension
	 */
	public function getSupportedAlternativeImages()
	{
		return $this->supportedAlternativeImages;
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
