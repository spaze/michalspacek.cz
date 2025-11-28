<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\EasterEgg\Nette;

use MichalSpacekCz\EasterEgg\NetteCve202015227;
use MichalSpacekCz\Templating\DefaultTemplate;
use MichalSpacekCz\Utils\Sleep;
use Nette\Application\UI\Presenter;

/**
 * @property-read DefaultTemplate $template
 */
final class NettePresenter extends Presenter
{

	public function __construct(
		private readonly NetteCve202015227 $cve202015227,
		private readonly Sleep $sleep,
	) {
		parent::__construct();
	}


	public function actionMicro(string $callback): void
	{
		$this->sleep->randomSleep(5, 20);
		$rce = $this->cve202015227->rce($callback, $this);
		$this->setView($rce->view->value);
		$this->template->setParametersArray($rce->parameters);
	}

}
