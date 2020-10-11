<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use Nette\Utils\Html;
use Spaze\PhpInfo\PhpInfo;

class InfoPresenter extends BasePresenter
{

	private PhpInfo $phpInfo;


	public function __construct(PhpInfo $phpInfo)
	{
		$this->phpInfo = $phpInfo;
		parent::__construct();
	}


	public function renderPhp(): void
	{
		$this->template->pageTitle = 'phpinfo()';
		$this->template->phpinfo = Html::el()->setHtml($this->phpInfo->getHtml());
	}

}
