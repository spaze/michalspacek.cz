<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

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


	protected function createComponentTlsCertificatesList(): TlsCertificatesList
	{
		return $this->tlsCertificatesListFactory->create($this->certificates->getNewest());
	}

}
