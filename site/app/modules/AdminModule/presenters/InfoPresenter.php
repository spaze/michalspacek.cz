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
		$info = preg_replace('~^.*?(<table[^>]*>.*</table>).*$~s', '$1', ob_get_clean());

		$this->template->pageTitle = 'phpinfo()';
		$this->template->phpinfo = \Nette\Utils\Html::el()->setHtml($info);
	}


}
