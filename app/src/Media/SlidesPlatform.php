<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use Uri\WhatWg\Url;

enum SlidesPlatform: string
{

	case SlideShare = 'SlideShare';
	case SpeakerDeck = 'Speaker Deck';


	public static function tryFromUrl(string $url): ?self
	{
		return match (Url::parse($url)?->getUnicodeHost()) {
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
