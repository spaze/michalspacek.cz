<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Heartbleed\Homepage;

use MichalSpacekCz\Presentation\Www\BasePresenter;

final class HomepagePresenter extends BasePresenter
{

	public function actionDefault(): void
	{
		$this->redirectPermanent(':Pulse:Heartbleed:');
	}

}
