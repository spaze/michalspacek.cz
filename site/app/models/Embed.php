<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * Embed model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Embed
{

	public const SLIDES_SLIDESHARE = 'slideshare';

	public const SLIDES_SPEAKERDECK = 'speakerdeck';

	public const VIDEO_VIMEO = 'vimeo';

	public const VIDEO_YOUTUBE = 'youtube';

	public const VIDEO_SLIDESLIVE = 'slideslive';

	/** @var \Spaze\ContentSecurityPolicy\Config */
	protected $contentSecurityPolicy;


	/**
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 */
	public function __construct(\Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy)
	{
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	/**
	 * Get template vars for slides
	 * @param \Nette\Database\Row $talk
	 * @param int|null $slide
	 * @return string[slidesEmbed, slidesDataSlide, slidesEmbedType]
	 */
	public function getSlidesTemplateVars(\Nette\Database\Row $talk, ?int $slide = null): array
	{
		$type = $this->getSlidesType($talk);
		if ($type !== null) {
			$this->contentSecurityPolicy->addSnippet($type);
		}

		$embedHref = $talk->slidesEmbed;
		$dataSlide = null;

		if ($slide !== null) {
			switch ($type) {
				case self::SLIDES_SLIDESHARE:
					$url = new \Nette\Http\Url($embedHref);
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
	 * @param \Nette\Database\Row $talk
	 * @return string[videoEmbed, videoEmbedType]
	 */
	public function getVideoTemplateVars(\Nette\Database\Row $talk): array
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
	 * @param \Nette\Database\Row $talk
	 * @return string|null
	 */
	private function getSlidesType(\Nette\Database\Row $talk): ?string
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
	 * @param \Nette\Database\Row $video
	 * @return string|null
	 */
	private function getVideoType(\Nette\Database\Row $video): ?string
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
