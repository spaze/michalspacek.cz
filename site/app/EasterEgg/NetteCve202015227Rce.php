<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

readonly class NetteCve202015227Rce
{

	public function __construct(
		public NetteCve202015227View $view,
		public string $eth0RxPackets = '',
		public string $eth1RxPackets = '',
		public string $loRxPackets = '',
		public string $eth0RxBytes = '',
		public string $eth1RxBytes = '',
		public string $loRxBytes = '',
		public string $eth0TxPackets = '',
		public string $eth1TxPackets = '',
		public string $loTxPackets = '',
		public string $eth0TxBytes = '',
		public string $eth1TxBytes = '',
		public string $loTxBytes = '',
		public string $command = '',
	) {
	}

}
