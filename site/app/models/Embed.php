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

	/** @var \Nette\Application\LinkGenerator */
	protected $linkGenerator;

	/** @var \Spaze\ContentSecurityPolicy\Config */
	protected $contentSecurityPolicy;


	/**
	 * @param \Nette\Application\LinkGenerator $linkGenerator
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 */
	public function __construct(\Nette\Application\LinkGenerator $linkGenerator, \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy)
	{
		$this->linkGenerator = $linkGenerator;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	/**
	 * Get template vars for slides
	 * @param \Nette\Database\Row $talk
	 * @param int|null $slide
	 * @return string[slidesEmbed, slidesDataSlide, canonicalLink, slidesEmbedType]
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
			'canonicalLink' => ($slide !== null ? $this->linkGenerator->link('Www:Talks:talk', [$talk->action]) : null),
			'slidesEmbedType' => $type,
		);
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
				throw new \RuntimeException("Unknown slides type for {$talk->slidesHref}");
				break;
		}
		return $type;
	}


	/**
	 * @param string $href
	 * @return string
	 */
	public function getVideoType(string $href): string
	{
		switch (parse_url($href, PHP_URL_HOST)) {
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
				throw new \RuntimeException("Unknown video type for {$href}");
				break;
		}
		return $type;
	}

}
