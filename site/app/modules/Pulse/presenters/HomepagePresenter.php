<?php
declare(strict_types = 1);

namespace App\PulseModule\Presenters;

use App\WwwModule\Presenters\BasePresenter;

/**
 * Homepage presenter.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{

	/**
	 * Default action handler.
	 */
	public function actionDefault(): void
	{
		$this->template->pageTitle = null;
		$this->template->pageHeader = 'Pulse';
	}

}
