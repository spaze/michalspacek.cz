<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\DateList;

use MichalSpacekCz\Training\Dates\TrainingDate;

interface TrainingApplicationsListFactory
{

	/**
	 * @param list<TrainingDate> $dates
	 */
	public function create(array $dates, DateListOrder $order, bool $pastOnly = false): TrainingApplicationsList;

}
