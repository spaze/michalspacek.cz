<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Exceptions;

use Exception;
use Throwable;

final class HttpClientTlsCertificateNotCapturedException extends Exception
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct("TLS certificate wasn't captured, HttpClientRequest::setTlsCaptureCertificate(true) not called", previous: $previous);
	}

}
