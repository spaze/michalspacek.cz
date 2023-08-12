<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use Exception;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Talks\Exceptions\TalkDateTimeException;
use MichalSpacekCz\Talks\Exceptions\TalkDoesNotExistException;
use Nette\Database\Explorer;
use Nette\Utils\Html;

class Talks
{

	public function __construct(
		private readonly Explorer $database,
		private readonly TexyFormatter $texyFormatter,
		private readonly TalkFactory $talkFactory,
	) {
	}


	/**
	 * @return list<Talk>
	 * @throws ContentTypeException
	 */
	public function getAll(?int $limit = null): array
	{
		$query = 'SELECT
				t.id_talk AS id,
				t.key_locale AS localeId,
				l.locale,
				t.action,
				t.title,
				t.description,
				t.date,
				t.duration,
				t.href,
				t.slides_href IS NOT NULL OR EXISTS (SELECT * FROM talk_slides s WHERE s.key_talk = COALESCE(t.key_talk_slides, t.id_talk)) AS hasSlides,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_thumbnail AS videoThumbnail,
				t.video_thumbnail_alternative AS videoThumbnailAlternative,
				t.video_embed AS videoEmbed,
				t.event,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.favorite,
				t.key_talk_slides AS slidesTalkId,
				t.key_talk_filenames AS filenamesTalkId,
				t.key_superseded_by AS supersededById,
				ts.action AS supersededByAction,
				ts.title AS supersededByTitle,
				t.publish_slides AS publishSlides
			FROM talks t
			    LEFT JOIN locales l ON l.id_locale = t.key_locale
				LEFT JOIN talks ts ON t.key_superseded_by = ts.id_talk
			WHERE t.date <= NOW()
			ORDER BY t.date DESC
			LIMIT ?';

		$talks = [];
		foreach ($this->database->fetchAll($query, $limit ?? PHP_INT_MAX) as $row) {
			$talks[] = $this->talkFactory->createFromDatabaseRow($row);
		}
		return $talks;
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
	 * @return list<Talk>
	 * @throws ContentTypeException
	 */
	public function getUpcoming(): array
	{
		$query = 'SELECT
				t.id_talk AS id,
				t.key_locale AS localeId,
				l.locale,
				t.action,
				t.title,
				t.description,
				t.date,
				t.duration,
				t.href,
				t.slides_href IS NOT NULL OR EXISTS (SELECT * FROM talk_slides s WHERE s.key_talk = COALESCE(t.key_talk_slides, t.id_talk)) AS hasSlides,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_thumbnail AS videoThumbnail,
				t.video_thumbnail_alternative AS videoThumbnailAlternative,
				t.video_embed AS videoEmbed,
				t.event,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.favorite,
				t.key_talk_slides AS slidesTalkId,
				t.key_talk_filenames AS filenamesTalkId,
				t.key_superseded_by AS supersededById,
				ts.action AS supersededByAction,
				ts.title AS supersededByTitle,
				t.publish_slides AS publishSlides
			FROM talks t
			    LEFT JOIN locales l ON l.id_locale = t.key_locale
				LEFT JOIN talks ts ON t.key_superseded_by = ts.id_talk
			WHERE t.date > NOW()
			ORDER BY t.date';

		$talks = [];
		foreach ($this->database->fetchAll($query) as $row) {
			$talks[] = $this->talkFactory->createFromDatabaseRow($row);
		}
		return $talks;
	}


	/**
	 * @throws TalkDoesNotExistException
	 * @throws ContentTypeException
	 */
	public function get(string $name): Talk
	{
		$result = $this->database->fetch(
			'SELECT
				t.id_talk AS id,
				t.key_locale AS localeId,
				l.locale,
				t.action,
				t.title,
				t.description,
				t.date,
				t.duration,
				t.href,
				t.slides_href IS NOT NULL OR EXISTS (SELECT * FROM talk_slides s WHERE s.key_talk = COALESCE(t.key_talk_slides, t.id_talk)) AS hasSlides,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_thumbnail AS videoThumbnail,
				t.video_thumbnail_alternative AS videoThumbnailAlternative,
				t.video_embed AS videoEmbed,
				t.event,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.favorite,
				t.key_talk_slides AS slidesTalkId,
				t.key_talk_filenames AS filenamesTalkId,
				t.key_superseded_by AS supersededById,
				ts.action AS supersededByAction,
				ts.title AS supersededByTitle,
				t.publish_slides AS publishSlides
			FROM talks t
			    LEFT JOIN locales l ON l.id_locale = t.key_locale
				LEFT JOIN talks ts ON t.key_superseded_by = ts.id_talk
			WHERE t.action = ?',
			$name,
		);

		if (!$result) {
			throw new TalkDoesNotExistException(name: $name);
		}
		return $this->talkFactory->createFromDatabaseRow($result);
	}


	/**
	 * @throws TalkDoesNotExistException
	 * @throws ContentTypeException
	 */
	public function getById(int $id): Talk
	{
		$result = $this->database->fetch(
			'SELECT
				t.id_talk AS id,
				t.key_locale AS localeId,
				l.locale,
				t.action,
				t.title,
				t.description,
				t.date,
				t.duration,
				t.href,
				t.slides_href IS NOT NULL OR EXISTS (SELECT * FROM talk_slides s WHERE s.key_talk = COALESCE(t.key_talk_slides, t.id_talk)) AS hasSlides,
				t.slides_href AS slidesHref,
				t.slides_embed AS slidesEmbed,
				t.video_href AS videoHref,
				t.video_thumbnail AS videoThumbnail,
				t.video_thumbnail_alternative AS videoThumbnailAlternative,
				t.video_embed AS videoEmbed,
				t.event,
				t.event_href AS eventHref,
				t.og_image AS ogImage,
				t.transcript,
				t.favorite,
				t.key_talk_slides AS slidesTalkId,
				t.key_talk_filenames AS filenamesTalkId,
				t.key_superseded_by AS supersededById,
				ts.action AS supersededByAction,
				ts.title AS supersededByTitle,
				t.publish_slides AS publishSlides
			FROM talks t
			    LEFT JOIN locales l ON l.id_locale = t.key_locale
				LEFT JOIN talks ts ON t.key_superseded_by = ts.id_talk
			WHERE t.id_talk = ?',
			$id,
		);

		if (!$result) {
			throw new TalkDoesNotExistException(id: $id);
		}
		return $this->talkFactory->createFromDatabaseRow($result);
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
		int $localeId,
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
			$localeId,
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
		int $localeId,
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
			$localeId,
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
	 * @param Talk $talk
	 * @return Html<Html|string>
	 */
	public function pageTitle(string $translationKey, Talk $talk): Html
	{
		return $this->texyFormatter->translate($translationKey, [strip_tags($talk->getTitle()->render()), strip_tags($talk->getEvent()->render())]);
	}


	/**
	 * @return array{action:string|null, title:string, description:string|null, date:DateTime, duration:int|null, href:string|null, key_talk_slides:int|null, key_talk_filenames:int|null, slides_href:string|null, slides_embed:string|null, video_href:string|null, video_thumbnail:string|null, video_thumbnail_alternative:string|null, video_embed:string|null, event:string, event_href:string|null, og_image:string|null, transcript:string|null, favorite:string|null, key_superseded_by:int|null, publish_slides:bool}
	 * @throws TalkDateTimeException
	 */
	private function getAddUpdateParams(
		int $localeId,
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
			'key_locale' => $localeId,
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
