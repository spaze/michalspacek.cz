<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

interface SecurityTxtValidatorLambdaVersionMessageFactory
{

	public function create(): SecurityTxtValidatorLambdaVersionMessage;

}
