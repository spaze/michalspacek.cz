<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use OutOfBoundsException;
use Spaze\Encryption\Format\FormatMarker;
use Throwable;

class FormatMarkerMismatchException extends OutOfBoundsException
{

	public function __construct(FormatMarker $actualMarker, ?Throwable $previous = null)
	{
		// The message names the method with the class because the value may come from a different class
		// than the one throwing this, and also from a different method of the same class
		parent::__construct(match ($actualMarker) {
			FormatMarker::AuthenticatedPublicKeyV1 => 'Data was encrypted with AuthenticatedPublicKeyEncryption::encrypt(), decrypt it with AuthenticatedPublicKeyEncryption::decrypt()',
			FormatMarker::AuthenticatedPublicKeyWithAdV1 => 'Data was encrypted with AuthenticatedPublicKeyEncryption::encryptWithAd(), decrypt it with AuthenticatedPublicKeyEncryption::decryptWithAd()',
			FormatMarker::AnonymousPublicKeyV1 => 'Data was encrypted with AnonymousPublicKeyEncryption, decrypt it with AnonymousPublicKeyEncryption::decrypt()',
			FormatMarker::SymmetricKeyV1 => 'Data was encrypted with SymmetricKeyEncryption::encrypt(), decrypt it with SymmetricKeyEncryption::decrypt()',
			FormatMarker::SymmetricKeyWithAdV1 => 'Data was encrypted with SymmetricKeyEncryption::encryptWithAd(), decrypt it with SymmetricKeyEncryption::decryptWithAd()',
		}, previous: $previous);
	}

}
