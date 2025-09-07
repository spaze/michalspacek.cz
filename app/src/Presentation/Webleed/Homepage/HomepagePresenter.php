<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Webleed\Homepage;

use Nette\Utils\Html;

final class HomepagePresenter extends \MichalSpacekCz\Presentation\Www\BasePresenter
{

	public function actionDefault(): void
	{
		$this->template->vulnerable = 1543;
		$this->template->daysSince = 371;
		$this->template->smallPrint = $this->getSmallPrint();
	}


	private function getSmallPrint(): Html
	{
		$smallPrint = [
			 'Knocking on yer servar\'s ports since 2014.',
			 'Do you even scan?',
			 'Wow. So heart. Much bleed.',
			htmlspecialchars('<script>alert(\'XSS\');</script>'),
			'<a href="https://www.youtube.com/watch?v=DLzxrzFCyOs">admin</a>',
		];
		return Html::el()->setHtml($smallPrint[array_rand($smallPrint)]);
	}

}
