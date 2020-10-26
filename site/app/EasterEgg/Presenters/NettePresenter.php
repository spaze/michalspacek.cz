<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg\Presenters;

use MichalSpacekCz\EasterEgg\NetteCve202015227;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;

/**
 * @property-read Template $template
 */
class NettePresenter extends Presenter
{

	private NetteCve202015227 $cve202015227;


	public function __construct(NetteCve202015227 $cve202015227)
	{
		parent::__construct();
		$this->cve202015227 = $cve202015227;
	}


	public function actionMicro(string $callback): void
	{
		sleep(random_int(5, 20));
		[$view, $params] = $this->cve202015227->rce($callback, $this->getParameters());
		$this->setView($view);
		$this->template->setParameters($params);
	}

}
