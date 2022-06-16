<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg\Presenters;

use MichalSpacekCz\EasterEgg\NetteCve202015227;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;

/**
 * @property-read DefaultTemplate $template
 */
class NettePresenter extends Presenter
{

	public function __construct(
		private readonly NetteCve202015227 $cve202015227,
	) {
		parent::__construct();
	}


	public function actionMicro(string $callback): void
	{
		sleep(random_int(5, 20));
		[$view, $params] = $this->cve202015227->rce($callback, $this->getParameters());
		$this->setView($view);
		$this->template->setParameters($params);
	}

}
