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


	public function __construct(
		private readonly Config $contentSecurityPolicy,
	) {
	}


	/**
	 * @param Row<mixed> $talk
	 * @param int|null $slide
	 * @return array{slidesEmbed: string, slidesDataSlide: int|null, slidesEmbedType: string|null}
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

		return [
			'slidesEmbed' => $embedHref,
			'slidesDataSlide' => $dataSlide,
			'slidesEmbedType' => $type,
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

}
