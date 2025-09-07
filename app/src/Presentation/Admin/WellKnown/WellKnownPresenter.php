<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\WellKnown;

use MichalSpacekCz\Presentation\Admin\BasePresenter;

final class WellKnownPresenter extends BasePresenter
{

	public function actionChangePassword(): never
	{
		$this->redirect('User:changePassword');
	}

}
