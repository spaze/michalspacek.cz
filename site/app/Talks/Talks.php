<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use Exception;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Resources\TalkMediaResources;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Talks\Exceptions\TalkDateTimeException;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\Html;
use RuntimeException;

class Talks
{

	public function __construct(
		private readonly Explorer $database,
		private readonly TexyFormatter $texyFormatter,
		private readonly TalkMediaResources $talkMediaResources,
	) {
	}


	/**
	 * Get all talks, or almost all talks.
	 *
	 * @param int|null $limit
	 * @return list<Row>
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

		return array_values($result);
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
	 * @return list<Row>
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

		return array_values($result);
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
				if (!is_string($row[$item])) {
					throw new ShouldNotHappenException(sprintf("Item '%s' is a %s not a string", $item, get_debug_type($row[$item])));
				}
				$row[$item] = $this->texyFormatter->format($row[$item]);
			}
		}
		foreach (['description', 'transcript'] as $item) {
			if (isset($row[$item])) {
				if (!is_string($row[$item])) {
					throw new ShouldNotHappenException(sprintf("Item '%s' is a %s not a string", $item, get_debug_type($row[$item])));
				}
				$row[$item] = $this->texyFormatter->formatBlock($row[$item]);
			}
		}
		$row->videoThumbnailUrl = isset($row->videoThumbnail) ? $this->talkMediaResources->getImageUrl($row->talkId, $row->videoThumbnail) : null;
		$row->videoThumbnailAlternativeUrl = isset($row->videoThumbnailAlternative) ? $this->talkMediaResources->getImageUrl($row->talkId, $row->videoThumbnailAlternative) : null;
	}


	/**
	 * Get favorite talks.
	 *
	 * @return list<Html>
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
	 * Update talk data.
	 *
	 * @throws TalkDateTimeException
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
		$params = $this->getAddUpdateParams(
			$action,
			$title,
			$description,
			$date,
			$duration,
			$href,
			$slidesTalk,
			$filenamesTalk,
			$slidesHref,
			$slidesEmbed,
			$videoHref,
			$videoThumbnail,
			$videoThumbnailAlternative,
			$videoEmbed,
			$event,
			$eventHref,
			$ogImage,
			$transcript,
			$favorite,
			$supersededBy,
			$publishSlides,
		);
		$this->database->query('UPDATE talks SET ? WHERE id_talk = ?', $params, $id);
	}


	/**
	 * Insert talk data.
	 *
	 * @throws TalkDateTimeException
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
		$params = $this->getAddUpdateParams(
			$action,
			$title,
			$description,
			$date,
			$duration,
			$href,
			$slidesTalk,
			$filenamesTalk,
			$slidesHref,
			$slidesEmbed,
			$videoHref,
			$videoThumbnail,
			$videoThumbnailAlternative,
			$videoEmbed,
			$event,
			$eventHref,
			$ogImage,
			$transcript,
			$favorite,
			$supersededBy,
			$publishSlides,
		);
		$this->database->query('INSERT INTO talks', $params);
		return (int)$this->database->getInsertId();
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
	 * @return array{action:string|null, title:string, description:string|null, date:DateTime, duration:int|null, href:string|null, key_talk_slides:int|null, key_talk_filenames:int|null, slides_href:string|null, slides_embed:string|null, video_href:string|null, video_thumbnail:string|null, video_thumbnail_alternative:string|null, video_embed:string|null, event:string, event_href:string|null, og_image:string|null, transcript:string|null, favorite:string|null, key_superseded_by:int|null, publish_slides:bool}
	 * @throws TalkDateTimeException
	 */
	private function getAddUpdateParams(
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
	): array {
		try {
			$dateTime = new DateTime($date);
		} catch (Exception $e) {
			throw new TalkDateTimeException($date, $e);
		}
		return [
			'action' => (empty($action) ? null : $action),
			'title' => $title,
			'description' => (empty($description) ? null : $description),
			'date' => $dateTime,
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
		];
	}

}
