<?php
namespace MichalSpacekCz;

/**
 * Training dates model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingDates extends BaseModel
{

	const STATUS_CREATED   = 'CREATED';    // 1
	const STATUS_TENTATIVE = 'TENTATIVE';  // 2
	const STATUS_CONFIRMED = 'CONFIRMED';  // 3
	const STATUS_CANCELED  = 'CANCELED';   // 4

}
