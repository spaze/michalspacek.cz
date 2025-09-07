<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Homepage;

use MichalSpacekCz\Presentation\Www\BasePresenter;

final class HomepagePresenter extends BasePresenter
{

	public function actionDefault(): void
	{
		$this->template->pageTitle = null;
		$this->template->pageHeader = 'Pulse';
	}

}
