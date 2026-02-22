<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

interface FormValidatorTexyRuleFactory
{

	public function create(): FormValidatorTexyRule;

}
