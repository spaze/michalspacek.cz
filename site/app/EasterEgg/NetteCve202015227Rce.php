<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

/**
 * @phpstan-type NetteCve202015227RceParameters array{command: string, eth0RxPackets: string, eth0RxBytes: string, eth0TxPackets: string, eth0TxBytes: string, eth1RxPackets: string, eth1RxBytes: string, eth1TxPackets: string, eth1TxBytes: string, loRxPackets: string, loRxBytes: string, loTxPackets: string, loTxBytes: string}
 */
class NetteCve202015227Rce
{

	/**
	 * @phpstan-param NetteCve202015227RceParameters $parameters
	 */
	public function __construct(
		private readonly NetteCve202015227View $view,
		private readonly array $parameters,
	) {
	}


	public function getView(): NetteCve202015227View
	{
		return $this->view;
	}


	/**
	 * @phpstan-return NetteCve202015227RceParameters
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

}
