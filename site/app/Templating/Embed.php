<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Nette\Database\Row;
use Nette\Http\Url;
use Spaze\ContentSecurityPolicy\Config;

class Embed
{

	public const SLIDES_SLIDESHARE = 'slideshare';

	public const SLIDES_SPEAKERDECK = 'speakerdeck';

	public const VIDEO_VIMEO = 'vimeo';

	public const VIDEO_YOUTUBE = 'youtube';

	public const VIDEO_SLIDESLIVE = 'slideslive';

	private Config $contentSecurityPolicy;


	public function __construct(Config $contentSecurityPolicy)
	{
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	/**
	 * @param Row<mixed> $talk
	 * @param int|null $slide
	 * @return array<string, string|integer|null> with keys slidesEmbed, slidesDataSlide, slidesEmbedType
	 */
	public function getSlidesTemplateVars(Row $talk, ?int $slide = null): array
	{
		$type = $this->getSlidesType($talk);
		if ($type !== null) {
			$this->contentSecurityPolicy->addSnippet($type);
		}

		/** @var string $embedHref */
		$embedHref = $talk->slidesEmbed;
		$dataSlide = null;

		if ($slide !== null) {
			switch ($type) {
				case self::SLIDES_SLIDESHARE:
					$url = new Url($embedHref);
					$url->appendQuery('startSlide=' . $slide);
					$embedHref = $url->getAbsoluteUrl();
					break;

				case self::SLIDES_SPEAKERDECK:
					$dataSlide = $slide;
					break;
			}
		}

		return array(
			'slidesEmbed' => $embedHref,
			'slidesDataSlide' => $dataSlide,
			'slidesEmbedType' => $type,
		);
	}


	/**
	 * Get template vars for video.
	 *
	 * @param Row<mixed> $talk
	 * @return array{videoEmbed: string|null, videoEmbedType: string|null}
	 */
	public function getVideoTemplateVars(Row $talk): array
	{
		$type = $this->getVideoType($talk);
		if ($type !== null) {
			$this->contentSecurityPolicy->addSnippet($type);
		}

		return [
			'videoEmbed' => $talk->videoEmbed,
			'videoEmbedType' => $type,
		];
	}


	/**
	 * @param Row<mixed> $talk
	 * @return string|null
	 */
	private function getSlidesType(Row $talk): ?string
	{
		if (!$talk->slidesHref) {
			return null;
		}

		switch (parse_url($talk->slidesHref, PHP_URL_HOST)) {
			case 'www.slideshare.net':
				$type = self::SLIDES_SLIDESHARE;
				break;
			case 'speakerdeck.com':
				$type = self::SLIDES_SPEAKERDECK;
				break;
			default:
				$type = null;
				break;
		}
		return $type;
	}


	/**
	 * @param Row<mixed> $video
	 * @return string|null
	 */
	private function getVideoType(Row $video): ?string
	{
		if (!$video->videoHref) {
			return null;
		}

		switch (parse_url($video->videoHref, PHP_URL_HOST)) {
			case 'www.youtube.com':
				$type = self::VIDEO_YOUTUBE;
				break;
			case 'vimeo.com':
				$type = self::VIDEO_VIMEO;
				break;
			case 'slideslive.com':
				$type = self::VIDEO_SLIDESLIVE;
				break;
			default:
				$type = null;
				break;
		}
		return $type;
	}

}
