<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokens;

interface UserAuthTokenLifetime
{

	public function getTokenType(): UserAuthTokenType;


	/**
	 * @return string Relative time expression accepted by DateTimeImmutable, e.g. '5 minutes' or '14 days'
	 */
	public function getTtl(): string;

}
