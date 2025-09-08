<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\EasterEgg\Nette;

use MichalSpacekCz\EasterEgg\NetteCve202015227;
use MichalSpacekCz\Templating\DefaultTemplate;
use Nette\Application\UI\Presenter;

/**
 * @property-read DefaultTemplate $template
 */
final class NettePresenter extends Presenter
{

	public function __construct(
		private readonly NetteCve202015227 $cve202015227,
	) {
		parent::__construct();
	}


	public function actionMicro(string $callback): void
	{
		sleep(random_int(5, 20));
		$rce = $this->cve202015227->rce($callback, $this);
		$this->setView($rce->view->value);
		$this->template->eth0RxPackets = $rce->eth0RxPackets;
		$this->template->eth1RxPackets = $rce->eth1RxPackets;
		$this->template->loRxPackets = $rce->loRxPackets;
		$this->template->eth0RxBytes = $rce->eth0RxBytes;
		$this->template->eth1RxBytes = $rce->eth1RxBytes;
		$this->template->loRxBytes = $rce->loRxBytes;
		$this->template->eth0TxPackets = $rce->eth0TxPackets;
		$this->template->eth1TxPackets = $rce->eth1TxPackets;
		$this->template->loTxPackets = $rce->loTxPackets;
		$this->template->eth0TxBytes = $rce->eth0TxBytes;
		$this->template->eth1TxBytes = $rce->eth1TxBytes;
		$this->template->loTxBytes = $rce->loTxBytes;
		$this->template->command = $rce->command;
	}

}
