<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

/**
 * Why a value is not a URI, decided by `SecurityTxtUriValidator` when it refuses one and carried by the violation, so a violation rebuilt from JSON says why without the
 * value being checked again. An enum for the same reason as `SecurityTxtPreferredLanguagesCommonMistakeReason`: the wire carries only the case value, and the format comes
 * from a `match` here, so it cannot be forged.
 */
enum SecurityTxtNotUriReason: string
{

	/**
	 * A violation stored before it carried the reason.
	 */
	case Unspecified = 'unspecified';

	case Empty = 'empty';

	case AngleBrackets = 'angle-brackets';

	case Parentheses = 'parentheses';

	case SquareBrackets = 'square-brackets';

	case Quotes = 'quotes';

	case TextBeforeUri = 'text-before-uri';

	case EmailAddressInText = 'email-address-in-text';

	case MoreThanOneEmailAddress = 'more-than-one-email-address';

	case MoreThanOneContact = 'more-than-one-contact';

	case Label = 'label';

	case SchemeMissing = 'scheme-missing';

	case SchemeMissingEmail = 'scheme-missing-email';

	case SchemeMissingPhone = 'scheme-missing-phone';

	case SchemeMissingPhoneWithSpaces = 'scheme-missing-phone-with-spaces';

	case SchemeMissingHostname = 'scheme-missing-hostname';

	case SchemeMissingPath = 'scheme-missing-path';

	case SchemeMissingFingerprint = 'scheme-missing-fingerprint';

	case SchemeMissingFingerprintWithSpaces = 'scheme-missing-fingerprint-with-spaces';

	case NotRfc3986 = 'not-rfc-3986';

	case MoreThanOneUri = 'more-than-one-uri';

	case Space = 'space';

	case SpaceAfterScheme = 'space-after-scheme';

	case SpaceInPhoneNumber = 'space-in-phone-number';

	case SpaceAfterSchemeAndInPhoneNumber = 'space-after-scheme-and-in-phone-number';

	case SpaceInHost = 'space-in-host';

	case SpaceInPort = 'space-in-port';

	case SpaceInFingerprint = 'space-in-fingerprint';

	case ControlCharacter = 'control-character';

	case StrayPercent = 'stray-percent';

	case StrayPercentInHost = 'stray-percent-in-host';

	case Backslash = 'backslash';

	case Character = 'character';

	case CharacterInHost = 'character-in-host';

	case UnencodedCharacter = 'unencoded-character';

	case Ipv6WithoutBrackets = 'ipv6-without-brackets';

	case Ipv6Unclosed = 'ipv6-unclosed';

	case PortNotNumber = 'port-not-number';

	case HostMissing = 'host-missing';

	case UserInfo = 'user-info';

	case EmptyUserInfo = 'empty-user-info';

	case PortOutOfRange = 'port-out-of-range';

	case InvalidHost = 'invalid-host';

	case NotUrl = 'not-url';

	case EmailAddressMissing = 'email-address-missing';

	case NotEmailAddress = 'not-email-address';


	/**
	 * What follows the value in the message.
	 *
	 * @return literal-string
	 */
	public function getFormat(): string
	{
		return match ($this) {
			self::Unspecified => "isn't a valid URI",
			self::Empty => 'is empty',
			self::AngleBrackets => 'is in angle brackets',
			self::Parentheses => 'is in parentheses',
			self::SquareBrackets => 'is in square brackets',
			self::Quotes => 'is in quotes',
			self::TextBeforeUri => 'has text before the URI',
			self::EmailAddressInText => 'has text around the email address',
			self::MoreThanOneEmailAddress => 'lists more than one email address',
			self::MoreThanOneContact => 'lists more than one contact',
			self::Label => 'starts with a label',
			self::SchemeMissing,
			self::SchemeMissingEmail,
			self::SchemeMissingPhone,
			self::SchemeMissingPhoneWithSpaces,
			self::SchemeMissingHostname,
			self::SchemeMissingPath,
			self::SchemeMissingFingerprint,
			self::SchemeMissingFingerprintWithSpaces => "doesn't follow the URI syntax described in RFC 3986, the scheme is missing",
			self::NotRfc3986 => "doesn't follow the URI syntax described in RFC 3986",
			self::MoreThanOneUri => 'lists more than one URI',
			self::Space,
			self::SpaceAfterScheme,
			self::SpaceInPhoneNumber,
			self::SpaceAfterSchemeAndInPhoneNumber,
			self::SpaceInHost,
			self::SpaceInPort,
			self::SpaceInFingerprint => "doesn't follow the URI syntax described in RFC 3986, it contains a space",
			self::ControlCharacter => "doesn't follow the URI syntax described in RFC 3986, it contains a control character",
			self::StrayPercent, self::StrayPercentInHost => "doesn't follow the URI syntax described in RFC 3986, it contains a %% not followed by two hexadecimal digits",
			self::Backslash => "doesn't follow the URI syntax described in RFC 3986, it contains a backslash",
			self::Character => "doesn't follow the URI syntax described in RFC 3986, it contains %s, which has to be percent-encoded",
			self::CharacterInHost => "doesn't follow the URI syntax described in RFC 3986, it contains %s in the host",
			self::UnencodedCharacter => "doesn't follow the URI syntax described in RFC 3986, it contains a character that has to be percent-encoded",
			self::Ipv6WithoutBrackets => "doesn't follow the URI syntax described in RFC 3986, an IPv6 address has to be in square brackets",
			self::Ipv6Unclosed => "doesn't follow the URI syntax described in RFC 3986, the IPv6 address has no closing ]",
			self::PortNotNumber => "doesn't follow the URI syntax described in RFC 3986, the port isn't a number",
			self::HostMissing => 'has no host',
			self::UserInfo => "contains a user name or a password, which RFC 9110 doesn't allow in an HTTP or HTTPS URI",
			self::EmptyUserInfo => "has an @ before the host, which RFC 9110 doesn't allow in an HTTP or HTTPS URI",
			self::PortOutOfRange => 'has a port above 65535',
			self::InvalidHost => "has a host that isn't a valid domain name or IP address",
			self::NotUrl => "can't be parsed as a URL",
			self::EmailAddressMissing => 'has no email address after the "mailto" scheme',
			self::NotEmailAddress => "has something that isn't an email address after the \"mailto\" scheme",
		};
	}


	/**
	 * @return literal-string
	 */
	public function getHowToFix(): string
	{
		return match ($this) {
			self::Unspecified, self::Empty, self::SchemeMissing, self::NotRfc3986 => 'Use a URI as the value',
			self::AngleBrackets => 'Remove the angle brackets',
			self::Parentheses => 'Remove the parentheses',
			self::SquareBrackets => 'Remove the square brackets',
			self::Quotes => 'Remove the quotes',
			self::TextBeforeUri => 'Remove the text, leave just the URI',
			self::EmailAddressInText => 'Use just the email address, with the "mailto" scheme',
			self::MoreThanOneEmailAddress => 'Put each email address in a Contact field of its own, with the "mailto" scheme',
			self::MoreThanOneContact => 'Put each in a Contact field of its own, with the "mailto" or "tel" scheme where it has none',
			self::Label => 'Remove the label, leave just the value',
			self::SchemeMissingEmail => 'The value looks like an email address, add the "mailto" scheme',
			self::SchemeMissingPhone => 'The value looks like a phone number, add the "tel" scheme',
			self::SchemeMissingPhoneWithSpaces => 'The value looks like a phone number, add the "tel" scheme and use hyphens instead of the spaces',
			self::SchemeMissingHostname => 'The value looks like a hostname, add the "https" scheme',
			self::SchemeMissingPath => 'The value is a path, add the "https" scheme and the host',
			self::SchemeMissingFingerprint => 'The value looks like an OpenPGP key fingerprint, add the "openpgp4fpr" scheme',
			self::SchemeMissingFingerprintWithSpaces => 'The value looks like an OpenPGP key fingerprint, add the "openpgp4fpr" scheme and remove the spaces',
			self::MoreThanOneUri => 'Put each URI in a field of its own',
			self::Space => 'Remove the spaces, or percent-encode each one as %%20',
			self::SpaceAfterScheme => 'Remove the space after the scheme',
			self::SpaceInPhoneNumber => 'Use hyphens instead of the spaces',
			self::SpaceAfterSchemeAndInPhoneNumber => 'Remove the space after the scheme, and use hyphens instead of the other spaces',
			self::SpaceInHost => 'Remove the spaces from the host',
			self::SpaceInPort => 'Remove the spaces from the port',
			self::SpaceInFingerprint => 'Remove the spaces from the fingerprint',
			self::ControlCharacter => 'Remove the control characters',
			self::StrayPercent => 'Percent-encode the %% itself, as %%25',
			self::StrayPercentInHost => 'Remove the %% from the host',
			self::Backslash => 'Use a slash instead, or percent-encode the backslash as %%5C',
			self::Character => 'Percent-encode it as %s',
			self::CharacterInHost => 'Remove it from the host',
			self::UnencodedCharacter => 'Percent-encode the character',
			self::Ipv6WithoutBrackets => 'Put the IPv6 address in square brackets',
			self::Ipv6Unclosed => 'Add the ] after the IPv6 address',
			self::PortNotNumber, self::PortOutOfRange => 'Use a port number from 0 to 65535, or leave the port out',
			self::HostMissing => 'Put // and the host right after the scheme',
			self::UserInfo => 'Remove the user name, the password, and the @ after them',
			self::EmptyUserInfo => 'Remove the @',
			self::InvalidHost => 'Use a domain name or an IP address as the host',
			self::NotUrl => 'Use a URL as the value',
			self::EmailAddressMissing => 'Add the email address after the "mailto" scheme',
			self::NotEmailAddress => 'Use a whole email address, with a name before the @ and a domain after it',
		};
	}


	/**
	 * The values the format expects, taken from the character the violation carries for a reason that names one, so the two always agree on how many there are.
	 *
	 * @return list<string>
	 */
	public function getValues(?string $character): array
	{
		return $this === self::Character || $this === self::CharacterInHost ? [$character ?? ''] : [];
	}


	/**
	 * @return list<string>
	 */
	public function getHowToFixValues(?string $character): array
	{
		return $this === self::Character ? [rawurlencode($character ?? '')] : [];
	}

}
