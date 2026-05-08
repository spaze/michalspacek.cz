<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use Nette\Http\Session;

final readonly class PasskeySessionSectionFactory
{

	public function __construct(private Session $session)
	{
	}


	public function create(): PasskeySessionSection
	{
		return $this->session->getSection('passkey', PasskeySessionSection::class);
	}

}
