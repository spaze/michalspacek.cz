<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

use Override;

final class SecurityTxtContact extends SecurityTxtUriField implements SecurityTxtFieldValue
{

	#[Override]
	public function getField(): SecurityTxtField
	{
		return SecurityTxtField::Contact;
	}


	/**
	 * Not every character an address can have is allowed in a `mailto:` URI, in `mailto:foo#bar@example.com` the address would be `foo`, so those RFC 6068 doesn't allow
	 * are percent-encoded, an `@` before the last one too, `mailto:%22not%40me%22@example.org`, and a comma, which would separate two addresses.
	 */
	public static function email(string $email): self
	{
		$encode = fn(string $part): ?string => preg_replace_callback("~[^a-z0-9\\-._\\~!$'()*+:\\x80-\\xff]~i", fn(array $matches): string => rawurlencode($matches[0]), $part);
		$at = strrpos($email, '@');
		return new self('mailto:' . ($at === false ? $encode($email) : $encode(substr($email, 0, $at)) . '@' . $encode(substr($email, $at + 1))));
	}


	/**
	 * A space is not one of the visual separators a `tel:` URI allows, a hyphen is.
	 */
	public static function phone(string $phone): self
	{
		return new self('tel:' . str_replace(' ', '-', $phone));
	}

}
