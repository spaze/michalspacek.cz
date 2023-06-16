<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

enum TrainingDateStatus: string
{

	case Created = 'CREATED'; // 1
	case Tentative = 'TENTATIVE'; // 2
	case Confirmed = 'CONFIRMED'; // 3
	case Canceled = 'CANCELED'; // 4

}
