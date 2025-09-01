<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls\CertificatesList;

use MichalSpacekCz\Tls\Certificate;

interface TlsCertificatesListFactory
{

	/**
	 * @param list<Certificate> $certificates
	 */
	public function create(array $certificates): TlsCertificatesList;

}
