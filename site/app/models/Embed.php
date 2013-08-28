<?php
namespace MichalSpacekCz;

/**
 * Embed model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Embed extends BaseModel
{

	const EMBED_SLIDES_SLIDESHARE = 1;

	const EMBED_SLIDES_SPEAKERDECK = 2;

	const EMBED_VIDEO_VIMEO = 1;

	const EMBED_VIDEO_YOUTUBE = 2;


	public function getSlidesTemplateVars($href, $embedHref, $slide)
	{
		$type = $this->getSlidesType($href);
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
			'slidesEmbedType' => $type,
			'slidesDataSlide' => $dataSlide,
		);
	}


	public function getVideoTemplateVars($href, $embedHref)
	{
		return array(
			'videoEmbed'     => $embedHref,
			'videoEmbedType' => $this->getVideoType($href),
		);
	}


	private function getSlidesType($href)
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


	private function getVideoType($href)
	{
		$type = false;

		switch (parse_url($href, PHP_URL_HOST)) {
			case 'www.youtube.com':
				$type = self::EMBED_VIDEO_YOUTUBE;
				break;
			case 'vimeo.com':
				$type = self::EMBED_VIDEO_VIMEO;
				break;
		}

		return $type;
	}


}
