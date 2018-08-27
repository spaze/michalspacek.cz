<?php
declare(strict_types = 1);

namespace App\WebleedModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends \App\WwwModule\Presenters\BasePresenter
{

	public function actionDefault(): void
	{
		$this->template->vulnerable = 1543;
		$this->template->daysSince = 371;
		$this->template->smallPrint = $this->getSmallPrint();
	}


	/**
	 * @return \Nette\Utils\Html
	 */
	private function getSmallPrint(): \Nette\Utils\Html
	{
		$smallPrint = array(
			 'Knocking on yer servar\'s ports since 2014.',
			 'Do you even scan?',
			 'Wow. So heart. Much bleed.',
			htmlspecialchars('<script>alert(\'XSS\');</script>'),
			'<a href="https://www.youtube.com/watch?v=DLzxrzFCyOs">admin</a>',
		);
		return \Nette\Utils\Html::el()->setHtml($smallPrint[array_rand($smallPrint)]);
	}

}
