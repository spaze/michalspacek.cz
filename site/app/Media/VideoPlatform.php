<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

enum VideoPlatform: string
{

	case YouTube = 'YouTube';
	case Vimeo = 'Vimeo';
	case SlidesLive = 'SlidesLive';


	public static function tryFromUrl(string $url): ?self
	{
		return match (parse_url($url, PHP_URL_HOST)) {
			'youtube.com', 'www.youtube.com', 'youtu.be' => self::YouTube,
			'vimeo.com', 'www.vimeo.com' => self::Vimeo,
			'slideslive.com', 'www.slideslive.com' => self::SlidesLive,
			default => null,
		};
	}


	public function getName(): string
	{
		return $this->value;
	}

}
