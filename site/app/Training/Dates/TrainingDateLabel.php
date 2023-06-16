<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use Contributte\Translation\Translator;
use Nette\Utils\Json;

class TrainingDateLabel
{

	public function __construct(
		private readonly Translator $translator,
	) {
	}


	public function decodeLabel(?string $json): ?string
	{
		return ($json ? Json::decode($json)->{$this->translator->getDefaultLocale()} : null);
	}

}
