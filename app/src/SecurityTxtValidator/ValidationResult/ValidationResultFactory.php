<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

interface ValidationResultFactory
{

	public function create(ValidationResultTemplateParameters $templateParameters): ValidationResult;

}
