<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

class WellKnownPresenter extends BasePresenter
{

	public function actionChangePassword(): never
	{
		$this->redirect('User:changePassword');
	}

}
