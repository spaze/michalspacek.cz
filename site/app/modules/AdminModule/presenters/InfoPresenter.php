<?php
namespace AdminModule;

/**
 * Info presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class InfoPresenter extends BasePresenter
{


	public function renderPhp()
	{
		ob_start();
		phpinfo();
		$this->template->phpinfo = \Nette\Utils\Html::el()->setHtml(ob_get_clean());
	}


}
