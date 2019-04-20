<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use Nette\Utils\Html;

class InfoPresenter extends BasePresenter
{

	public function renderPhp(): void
	{
		ob_start();
		phpinfo();
		$info = preg_replace('~^.*?(<table[^>]*>.*</table>).*$~s', '$1', ob_get_clean());
		// Convert inline styles to classes defined in admin/info.css so we can drop CSP style-src 'unsafe-inline'
		$info = str_replace('style="color: #', 'class="color-', $info);

		$this->template->pageTitle = 'phpinfo()';
		$this->template->phpinfo = Html::el()->setHtml($info);
	}

}
