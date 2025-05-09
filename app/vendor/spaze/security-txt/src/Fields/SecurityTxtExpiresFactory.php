<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

use DateTimeImmutable;

final class SecurityTxtExpiresFactory
{

	public function create(DateTimeImmutable $dateTime): SecurityTxtExpires
	{
		$interval = new DateTimeImmutable()->diff($dateTime);
		$isExpired = $interval->invert === 1;
		$days = (int)$interval->days; // $interval is created by diff() so days is always set
		return new SecurityTxtExpires($dateTime, $isExpired, $isExpired ? -$days : $days);
	}

}
