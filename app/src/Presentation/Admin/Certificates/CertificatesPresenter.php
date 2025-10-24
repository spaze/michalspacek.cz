<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Certificates;

use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\Tls\Certificates;
use MichalSpacekCz\Tls\CertificatesList\TlsCertificatesList;
use MichalSpacekCz\Tls\CertificatesList\TlsCertificatesListFactory;

final class CertificatesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Certificates $certificates,
		private readonly TlsCertificatesListFactory $tlsCertificatesListFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = 'HTTPS certifikáty';
	}


	public function actionHowItWorks(): void
	{
		$this->template->pageTitle = 'Jak funguje kontrola HTTPS certifikátů';
	}


	protected function createComponentTlsCertificatesList(): TlsCertificatesList
	{
		return $this->tlsCertificatesListFactory->create($this->certificates->getNewest());
	}

}
