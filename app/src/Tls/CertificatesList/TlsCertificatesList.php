<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls\CertificatesList;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Tls\Certificate;

final class TlsCertificatesList extends UiControl
{

	/**
	 * @param list<Certificate> $certificates
	 */
	public function __construct(
		private readonly array $certificates,
	) {
	}


	public function render(): void
	{
		$this->template->certificates = $this->certificates;
		$this->template->render(__DIR__ . '/tlsCertificatesList.latte');
	}

}
