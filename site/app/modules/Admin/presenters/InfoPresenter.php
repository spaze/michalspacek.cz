<?php
namespace App\AdminModule\Presenters;

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
		// Convert inline styles to classes defined in admin/info.css so we can drop CSP style-src 'unsafe-inline'
		$info = str_replace('style="color: #', 'class="color-', $info);

		$this->template->pageTitle = 'phpinfo()';
		$this->template->phpinfo = \Nette\Utils\Html::el()->setHtml($info);
	}

}
