<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use Contributte\Translation\Translator;
use MichalSpacekCz\EasterEgg\FourOhFourButFound\FourOhFourButFound;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

final readonly class FormValidators
{

	/**
	 * Lower case only, a URL is looked up exactly as stored, so a slug differing just in case would be another page
	 */
	private const string SLUG_PATTERN = '[a-z0-9.,_-]+';


	public function __construct(
		private Translator $translator,
		private FourOhFourButFound $fourOhFourButFound,
	) {
	}


	public function addValidateSlugRules(TextInput $input, string $pattern = self::SLUG_PATTERN): void
	{
		$input
			->addRule(Form::Pattern, $this->translator->translate('messages.forms.validateSlugParamsError'), $pattern)
			->addRule(Form::IsNotIn, $this->translator->translate('messages.forms.validateSlugParamsEasterEgg'), $this->fourOhFourButFound->getRequestSubstrings());
	}

}
