<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use Contributte\Translation\Translator;

class Strings
{

	public function __construct(
		private readonly Translator $translator,
	) {
	}


	/**
	 * Get initial letter because sometimes it might be two letters.
	 */
	public function getInitialLetterUppercase(string $string): string
	{
		$length = ($this->translator->getDefaultLocale() === 'cs_CZ' && mb_strtolower(mb_substr($string, 0, 2)) === 'ch' ? 2 : 1);
		$initial = mb_substr($string, 0, $length);
		return mb_strtoupper(mb_substr($initial, 0, 1)) . mb_strtolower(mb_substr($initial, 1));
	}

}
