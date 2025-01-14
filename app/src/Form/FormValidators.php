<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Contributte\Translation\Translator;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

readonly class FormValidators
{

	public function __construct(
		private Translator $translator,
	) {
	}


	public function addValidateSlugRules(TextInput $input): void
	{
		$input->addRule(Form::Pattern, $this->translator->translate('messages.forms.validateSlugParamsError'), '[a-z0-9.,_-]+');
	}

}
