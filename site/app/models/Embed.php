<?php
namespace MichalSpacekCz;

/**
 * Embed model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Embed
{

	const EMBED_SLIDES_SLIDESHARE = 'slideshare';

	const EMBED_SLIDES_SPEAKERDECK = 'speakerdeck';

	const EMBED_VIDEO_VIMEO = 'vimeo';

	const EMBED_VIDEO_YOUTUBE = 'youtube';

	const EMBED_VIDEO_SLIDESLIVE = 'slideslive';


	public function getSlidesTemplateVars($type, $embedHref, $slide)
	{
		$dataSlide = null;

		if ($slide !== null) {
			switch ($type) {
				case self::EMBED_SLIDES_SLIDESHARE:
					$url = new \Nette\Http\Url($embedHref);
					$url->appendQuery('startSlide=' . $slide);
					$embedHref = $url->getAbsoluteUrl();
					break;

				case self::EMBED_SLIDES_SPEAKERDECK:
					$dataSlide = $slide;
					break;
			}
		}

		return array(
			'slidesEmbed'     => $embedHref,
			'slidesDataSlide' => $dataSlide,
		);
	}


	public function getSlidesType($href)
	{
		$type = false;

		switch (parse_url($href, PHP_URL_HOST)) {
			case 'www.slideshare.net':
				$type = self::EMBED_SLIDES_SLIDESHARE;
				break;
			case 'speakerdeck.com':
				$type = self::EMBED_SLIDES_SPEAKERDECK;
				break;
		}

		return $type;
	}


	public function getVideoType($href)
	{
		$type = false;

		switch (parse_url($href, PHP_URL_HOST)) {
			case 'www.youtube.com':
				$type = self::EMBED_VIDEO_YOUTUBE;
				break;
			case 'vimeo.com':
				$type = self::EMBED_VIDEO_VIMEO;
				break;
			case 'slideslive.com':
				$type = self::EMBED_VIDEO_SLIDESLIVE;
				break;
		}

		return $type;
	}

}
