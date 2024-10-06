<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

enum TrainingDateStatus: string
{

	case Created = 'CREATED'; // 1
	case Tentative = 'TENTATIVE'; // 2
	case Confirmed = 'CONFIRMED'; // 3
	case Canceled = 'CANCELED'; // 4


	public function id(): int
	{
		return match ($this) {
			self::Created => 1,
			self::Tentative => 2,
			self::Confirmed => 3,
			self::Canceled => 4,
		};
	}


	/**
	 * The same description as what's stored in database in training_date_status.description.
	 */
	public function description(): string
	{
		return match ($this) {
			self::Created => 'Displayed in admin only',
			self::Tentative => 'Displayed on the site as month, tentative signup',
			self::Confirmed => 'Displayed on the site with full date, regular signup',
			self::Canceled => 'Displayed only in admin',
		};
	}

}
