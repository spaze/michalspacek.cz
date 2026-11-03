<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fields\SecurityTxtField;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\SecurityTxtUriValidator;
use Spaze\SecurityTxt\Violations\SecurityTxtFieldUriInWellKnown;

/**
 * RFC 9116 Section 6 lets a value point under `/.well-known/` only at a name registered with IANA, which can't be told without a copy of the registry, so only the fields
 * with no reason to point there are checked, and only warned about. A value that isn't a URI is checked as the one suggested for it, so that taking the suggestion doesn't bring
 * up a warning the value didn't get. Only an HTTP or HTTPS URI, RFC 8615 defines well-known URIs for those, an `ftp:` one has none. Not Canonical, which points to the file
 * itself, nor CSAF, whose `/.well-known/csaf/` is registered.
 */
final readonly class UriCheckFieldValueInWellKnown implements FieldProcessor
{

	public function __construct(
		private SecurityTxtField $field,
	) {
	}


	/**
	 * @throws SecurityTxtWarning
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$url = new SecurityTxtUriValidator()->getUrl($value, $this->field, $securityTxt->getFileLocation());
		if ($url !== null && in_array($url->getScheme(), ['http', 'https'], true) && str_starts_with($url->getPath(), '/.well-known/')) {
			throw new SecurityTxtWarning(new SecurityTxtFieldUriInWellKnown($this->field, $value));
		}
	}

}
