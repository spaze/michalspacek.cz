<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

final class WellKnownPresenter extends BasePresenter
{

	public function actionChangePassword(): never
	{
		$this->redirect('User:changePassword');
	}

}
