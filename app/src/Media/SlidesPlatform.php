<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

enum SlidesPlatform: string
{

	case SlideShare = 'SlideShare';
	case SpeakerDeck = 'Speaker Deck';


	public static function tryFromUrl(string $url): ?self
	{
		return match (parse_url($url, PHP_URL_HOST)) {
			'slideshare.net', 'www.slideshare.net' => self::SlideShare,
			'speakerdeck.com', 'www.speakerdeck.com' => self::SpeakerDeck,
			default => null,
		};
	}


	public function getName(): string
	{
		return $this->value;
	}

}
