<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * Talks model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Talks
{

	/** @var integer */
	private const SLIDE_MAX_WIDTH = 800;

	/** @var integer */
	private const SLIDE_MAX_HEIGHT = 450;

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \Nette\Http\Request */
	protected $httpRequest;

	/**
	 * Slides root, just directory no FQND, no leading slash, no trailing slash.
	 *
	 * @var string
	 */
	protected $slidesRoot;

	/**
	 * Static files root FQDN, no trailing slash.
	 *
	 * @var string
	 */
	protected $staticRoot;

	/**
	 * Physical location root directory, no trailing slash.
	 *
	 * @var string
	 */
	protected $locationRoot;

	/** @var string[] */
	private $deleteFiles = [];

	/** @var integer[] */
	private $otherSlides = [];

	/** @var string[] */
	private $supportedImages = [
		'image/gif' => 'gif',
		'image/png' => 'png',
		'image/jpeg' => 'jpg',
	];

	/** @var string[] */
	private $supportedAlternativeImages = [
		'image/webp' => 'webp',
	];


	/**
	 * Contructor.
	 *
	 * @param \Nette\Database\Context $context
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \Nette\Http\Request $httpRequest
	 */
	public function __construct(\Nette\Database\Context $context, \MichalSpacekCz\Formatter\Texy $texyFormatter, \Nette\Http\Request $httpRequest)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->httpRequest = $httpRequest;
	}


	/**
	 * Set static content URL root.
	 *
	 * @param string $root
	 */
	public function setStaticRoot($root)
	{
		$this->staticRoot = rtrim($root, '/');
	}


	/**
	 * Set location root directory.
	 *
	 * @param string $root
	 */
	public function setLocationRoot($root)
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
	 * @return \Nette\Database\Row[]
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
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
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
		return (int)($count/10)*10;
	}


	/**
	 * Get upcoming talks.
	 *
	 * @return \Nette\Database\Row[]
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
	 * @return \Nette\Database\Row
	 */
	public function get(string $name): \Nette\Database\Row
	{
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
			throw new \RuntimeException("I haven't talked about {$name}, yet");
		}

		$this->format($result);
		return $result;
	}


	/**
	 * Get talk data by id.
	 *
	 * @param integer $id
	 * @return \Nette\Database\Row
	 */
	public function getById(int $id): \Nette\Database\Row
	{
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
			throw new \RuntimeException("I haven't talked about id {$id}, yet");
		}

		$this->format($result);
		return $result;
	}


	/**
	 * @param \Nette\Database\Row $row
	 */
	private function format(\Nette\Database\Row $row): void
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
	 * @return \Nette\Database\Row[]
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
				throw new \RuntimeException("Unknown slide {$slide} for talk {$talkId}");
			}
		}
		return $slideNo;
	}


	/**
	 * Update talk data.
	 *
	 * @param integer $id
	 * @param string|null $action
	 * @param string $title
	 * @param string|null $description
	 * @param string $date
	 * @param integer|null $duration
	 * @param string|null $href
	 * @param integer|null $slidesTalk
	 * @param string|null $slidesHref
	 * @param string|null $slidesEmbed
	 * @param string|null $videoHref
	 * @param string|null $videoEmbed
	 * @param string $event
	 * @param string|null $eventHref
	 * @param string|null $ogImage
	 * @param string|null $transcript
	 * @param string|null $favorite
	 * @param integer|null $supersededBy
	 * @param boolean $publishSlides
	 */
	public function update(int $id, ?string $action, string $title, ?string $description, string $date, ?int $duration, ?string $href, ?int $slidesTalk, ?string $slidesHref, ?string $slidesEmbed, ?string $videoHref, ?string $videoEmbed, string $event, ?string $eventHref, ?string $ogImage, ?string $transcript, ?string $favorite, ?int $supersededBy, bool $publishSlides): void
	{
		$this->database->query(
			'UPDATE talks SET ? WHERE id_talk = ?',
			array(
				'action' => (empty($action) ? null : $action),
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new \DateTime($date),
				'duration' => (empty($duration) ? null : $duration),
				'href' => (empty($href) ? null : $href),
				'key_talk_slides' => (empty($slidesTalk) ? null : $slidesTalk),
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
	 *
	 * @param string|null $action
	 * @param string $title
	 * @param string|null $description
	 * @param string $date
	 * @param integer|null $duration
	 * @param string|null $href
	 * @param integer|null $slidesTalk
	 * @param string|null $slidesHref
	 * @param string|null $slidesEmbed
	 * @param string|null $videoHref
	 * @param string|null $videoEmbed
	 * @param string $event
	 * @param string|null $eventHref
	 * @param string|null $ogImage
	 * @param string|null $transcript
	 * @param string|null $favorite
	 * @param integer|null $supersededBy
	 * @param boolean $publishSlides
	 */
	public function add(?string $action, string $title, ?string $description, string $date, ?int $duration, ?string $href, ?int $slidesTalk, ?string $slidesHref, ?string $slidesEmbed, ?string $videoHref, ?string $videoEmbed, string $event, ?string $eventHref, ?string $ogImage, ?string $transcript, ?string $favorite, ?int $supersededBy, bool $publishSlides): void
	{
		$this->database->query(
			'INSERT INTO talks',
			array(
				'action' => (empty($action) ? null : $action),
				'title' => $title,
				'description' => (empty($description) ? null : $description),
				'date' => new \DateTime($date),
				'duration' => (empty($duration) ? null : $duration),
				'href' => (empty($href) ? null : $href),
				'key_talk_slides' => (empty($slidesTalk) ? null : $slidesTalk),
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
	 * @return \Nette\Database\Row[]
	 */
	public function getSlides(int $talkId): array
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
		$result = [];
		$alternativeTypes = array_flip($this->supportedAlternativeImages);
		foreach ($slides as $row) {
			$row->speakerNotes = $this->texyFormatter->format($row->speakerNotesTexy);
			$row->image = $this->getSlideImageFilename($this->staticRoot, $talkId, $row->filename);
			$row->imageAlternative = $this->getSlideImageFilename($this->staticRoot, $talkId, $row->filenameAlternative);
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
	 * @param \Nette\Http\FileUpload $replace
	 * @param string[] $supported
	 * @param bool $removeFile
	 * @param string|null $originalFile
	 * @param integer $width
	 * @param integer $height
	 * @return null|string
	 */
	private function replaceSlideImage(int $talkId, \Nette\Http\FileUpload $replace, array $supported, bool $removeFile, ?string $originalFile, int &$width, int &$height): ?string
	{
		if (!$replace->hasFile()) {
			return null;
		}
		if (!$replace->isOk()) {
			throw new \RuntimeException('Slide image upload failed', $replace->getError());
		}
		if (!in_array($replace->getContentType(), array_keys($supported))) {
			throw new \RuntimeException('Slide image type not allowed: ' . $replace->getContentType());
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
	 * @param \Nette\Utils\ArrayHash $slides
	 * @throws \UnexpectedValueException on duplicate entry (key_talk, number)
	 * @throws \PDOException
	 */
	private function addSlides(int $talkId, \Nette\Utils\ArrayHash $slides): void
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
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == \Nette\Database\Drivers\MySqlDriver::ERROR_DUPLICATE_ENTRY) {
					throw new \UnexpectedValueException($e->getMessage(), $lastNumber);
				}
			}
			throw $e;
		}
	}


	/**
	 * Update slides.
	 *
	 * @param integer $talkId
	 * @param \Nette\Utils\ArrayHash $slides
	 * @param bool $removeFiles Remove old files?
	 * @throws \UnexpectedValueException on duplicate entry (key_talk, number)
	 * @throws \PDOException
	 */
	private function updateSlides(int $talkId, array $originalSlides, \Nette\Utils\ArrayHash $slides, bool $removeFiles): void
	{
		$lastNumber = 0;
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
				$replace = $this->replaceSlideImage($talkId, $slide->replace, $this->supportedImages, $removeFiles, $originalSlides[$slide->number]->filename, $width, $height);
				$replaceAlternative = $this->replaceSlideImage($talkId, $slide->replaceAlternative, $this->supportedAlternativeImages, $removeFiles, $originalSlides[$slide->number]->filenameAlternative, $width, $height);
				if ($removeFiles) {
					// TODO delete renamed files
				}

				$lastNumber = (int)$slide->number;
				$this->database->query(
					'UPDATE talk_slides SET ? WHERE id_slide = ?',
					array(
						'key_talk' => $talkId,
						'alias' => $slide->alias,
						'number' => $slide->number,
						'filename' => $replace ?: $slide->filename,
						'filename_alternative' => $replaceAlternative ?: $slide->filenameAlternative,
						'title' => $slide->title,
						'speaker_notes' => $slide->speakerNotes,
					),
					$id
				);
			}
		} catch (\PDOException $e) {
			if ($e->getCode() == '23000') {
				if ($e->errorInfo[1] == \Nette\Database\Drivers\MySqlDriver::ERROR_DUPLICATE_ENTRY) {
					throw new \UnexpectedValueException($e->getMessage(), $lastNumber);
				}
			}
			throw $e;
		}
	}


	/**
	 * Save new slides.
	 *
	 * @param int $talkId
	 * @param \Nette\Database\Row[] $originalSlides
	 * @param \Nette\Utils\ArrayHash $newSlides
	 */
	public function saveSlides(int $talkId, array $originalSlides, \Nette\Utils\ArrayHash $newSlides): void
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
	 * @param \Nette\Database\Row $talk
	 * @return \Nette\Utils\Html
	 */
	public function pageTitle(string $translationKey, \Nette\Database\Row $talk): \Nette\Utils\Html
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
		return \Nette\Utils\Json::encode([
			'ratio' => ['width' => 16, 'height' => 9],
			'max' => ['width' => self::SLIDE_MAX_WIDTH, 'height' => self::SLIDE_MAX_HEIGHT],
		]);
	}

}
