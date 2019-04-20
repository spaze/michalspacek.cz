<?php
declare(strict_types = 1);

namespace App\WebleedModule\Presenters;

use App\WwwModule\Presenters\BasePresenter;
use Nette\Utils\Html;

class HomepagePresenter extends BasePresenter
{

	public function actionDefault(): void
	{
		$this->template->vulnerable = 1543;
		$this->template->daysSince = 371;
		$this->template->smallPrint = $this->getSmallPrint();
	}


	private function getSmallPrint(): Html
	{
		$smallPrint = array(
			 'Knocking on yer servar\'s ports since 2014.',
			 'Do you even scan?',
			 'Wow. So heart. Much bleed.',
			htmlspecialchars('<script>alert(\'XSS\');</script>'),
			'<a href="https://www.youtube.com/watch?v=DLzxrzFCyOs">admin</a>',
		);
		return Html::el()->setHtml($smallPrint[array_rand($smallPrint)]);
	}

}
