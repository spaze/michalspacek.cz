<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\IntegrityPolicy;

interface IntegrityPolicy
{

	public const string HEADER_NAME = 'Integrity-Policy';


	public function set(): void;

}
