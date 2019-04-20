<?php
declare(strict_types = 1);

namespace App\PulseModule\Presenters;

use App\WwwModule\Presenters\BasePresenter;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault(): void
	{
		$this->template->pageTitle = null;
		$this->template->pageHeader = 'Pulse';
	}

}
