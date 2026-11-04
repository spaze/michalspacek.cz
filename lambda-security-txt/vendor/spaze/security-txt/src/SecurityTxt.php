<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt;

use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fields\SecurityTxtAcknowledgments;
use Spaze\SecurityTxt\Fields\SecurityTxtBugBounty;
use Spaze\SecurityTxt\Fields\SecurityTxtCanonical;
use Spaze\SecurityTxt\Fields\SecurityTxtContact;
use Spaze\SecurityTxt\Fields\SecurityTxtCsaf;
use Spaze\SecurityTxt\Fields\SecurityTxtEncryption;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtField;
use Spaze\SecurityTxt\Fields\SecurityTxtFieldValue;
use Spaze\SecurityTxt\Fields\SecurityTxtHiring;
use Spaze\SecurityTxt\Fields\SecurityTxtPolicy;
use Spaze\SecurityTxt\Fields\SecurityTxtPreferredLanguages;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifyResult;
use Spaze\SecurityTxt\Violations\SecurityTxtAcknowledgmentsNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtAcknowledgmentsNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtContactNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtContactNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtCsafNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtCsafNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtCsafWrongFile;
use Spaze\SecurityTxt\Violations\SecurityTxtEncryptionNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtEncryptionNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtExpired;
use Spaze\SecurityTxt\Violations\SecurityTxtExpiresTooLong;
use Spaze\SecurityTxt\Violations\SecurityTxtFileLocationNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtFileLocationNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtHiringNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtHiringNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtPolicyNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtPolicyNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtPreferredLanguagesCommonMistake;
use Spaze\SecurityTxt\Violations\SecurityTxtPreferredLanguagesCommonMistakeReason;
use Spaze\SecurityTxt\Violations\SecurityTxtPreferredLanguagesEmpty;
use Spaze\SecurityTxt\Violations\SecurityTxtPreferredLanguagesWrongLanguageTags;
use Uri\WhatWg\Url;

final class SecurityTxt implements JsonSerializable
{

	private const string LANGUAGE_TAG = '/^([a-z]{2,3}(-[a-z0-9]+)*|[xi]-[a-z0-9]+)$/i';

	/**
	 * How people get the Czech language tag wrong: `cz`, the country code, or `cze`, the ISO 639-2 code, which RFC 5646 doesn't use because Czech has the two-letter code `cs`
	 */
	private const string CZECH_MISTAKE = '/^cze?(?=-|$)/i';

	/**
	 * Languages by the English names written instead of their tags, in lowercase
	 */
	private const array LANGUAGE_NAMES = [
		'czech' => 'cs',
		'dutch' => 'nl',
		'english' => 'en',
		'french' => 'fr',
		'german' => 'de',
		'hungarian' => 'hu',
		'italian' => 'it',
		'polish' => 'pl',
		'portuguese' => 'pt',
		'russian' => 'ru',
		'slovak' => 'sk',
		'spanish' => 'es',
		'ukrainian' => 'uk',
	];

	private ?string $fileLocation = null;
	private ?SecurityTxtExpires $expires = null;
	private ?SecurityTxtSignatureVerifyResult $signatureVerifyResult = null;
	private ?SecurityTxtPreferredLanguages $preferredLanguages = null;
	private ?SecurityTxtBugBounty $bugBounty = null;

	/**
	 * @var list<SecurityTxtCanonical>
	 */
	private array $canonical = [];

	/**
	 * @var list<SecurityTxtContact>
	 */
	private array $contact = [];

	/**
	 * @var list<SecurityTxtAcknowledgments>
	 */
	private array $acknowledgments = [];

	/**
	 * @var list<SecurityTxtHiring>
	 */
	private array $hiring = [];

	/**
	 * @var list<SecurityTxtPolicy>
	 */
	private array $policy = [];

	/**
	 * @var list<SecurityTxtEncryption>
	 */
	private array $encryption = [];

	/**
	 * @var list<SecurityTxtCsaf>
	 */
	private array $csaf = [];

	/**
	 * @var list<SecurityTxtFieldValue>
	 */
	private array $fields = [];


	public function __construct(
		private readonly SecurityTxtValidationLevel $validationLevel = SecurityTxtValidationLevel::NoInvalidValues,
	) {
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function setFileLocation(string $fileLocation): void
	{
		$this->setValue(
			function () use ($fileLocation): void {
				$this->fileLocation = $fileLocation;
			},
			function () use ($fileLocation): void {
				$this->checkUri($fileLocation, SecurityTxtFileLocationNotUri::class, SecurityTxtFileLocationNotHttps::class, fetched: true);
			},
		);
	}


	public function getFileLocation(): ?string
	{
		return $this->fileLocation;
	}


	/**
	 * @throws SecurityTxtError
	 * @throws SecurityTxtWarning
	 */
	public function setExpires(SecurityTxtExpires $expires): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtExpires => $this->expires = $expires,
			function () use ($expires): void {
				if ($expires->isExpired()) {
					throw new SecurityTxtError(new SecurityTxtExpired());
				}
			},
			function () use ($expires): void {
				if ($expires->inDays() > 366) {
					throw new SecurityTxtWarning(new SecurityTxtExpiresTooLong());
				}
			},
		);
	}


	public function getExpires(): ?SecurityTxtExpires
	{
		return $this->expires;
	}


	public function withSignatureVerifyResult(SecurityTxtSignatureVerifyResult $signatureVerifyResult): self
	{
		$clone = clone $this;
		$clone->signatureVerifyResult = $signatureVerifyResult;
		return $clone;
	}


	public function getSignatureVerifyResult(): ?SecurityTxtSignatureVerifyResult
	{
		return $this->signatureVerifyResult;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function addCanonical(SecurityTxtCanonical $canonical): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtCanonical => $this->canonical[] = $canonical,
			function () use ($canonical): void {
				$this->checkUri($canonical->getUri(), SecurityTxtCanonicalNotUri::class, SecurityTxtCanonicalNotHttps::class, SecurityTxtField::Canonical);
			},
		);
	}


	/**
	 * @return list<SecurityTxtCanonical>
	 */
	public function getCanonical(): array
	{
		return $this->canonical;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function addContact(SecurityTxtContact $contact): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtContact => $this->contact[] = $contact,
			function () use ($contact): void {
				$this->checkUri($contact->getUri(), SecurityTxtContactNotUri::class, SecurityTxtContactNotHttps::class, SecurityTxtField::Contact);
			},
		);
	}


	/**
	 * @return list<SecurityTxtContact>
	 */
	public function getContact(): array
	{
		return $this->contact;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function setPreferredLanguages(SecurityTxtPreferredLanguages $preferredLanguages): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtPreferredLanguages => $this->preferredLanguages = $preferredLanguages,
			function () use ($preferredLanguages): void {
				if ($preferredLanguages->getLanguages() === []) {
					throw new SecurityTxtError(new SecurityTxtPreferredLanguagesEmpty());
				}
				$wrongLanguages = [];
				foreach ($preferredLanguages->getLanguages() as $key => $value) {
					if (preg_match(self::LANGUAGE_TAG, $value) !== 1) {
						$wrongLanguages[$key + 1] = $value;
					}
				}
				// The value to use is there only when every tag can be corrected, or it would still be wrong. Each tag is in it once, however it's written, so `EN, English` becomes `EN`
				$correctLanguages = array_filter(array_map($this->getLanguageTag(...), $preferredLanguages->getLanguages()), fn(?string $tag): bool => $tag !== null);
				$correctValue = count($correctLanguages) === count($preferredLanguages->getLanguages())
					? implode(', ', array_intersect_key($correctLanguages, array_unique(array_map(strtolower(...), $correctLanguages))))
					: null;
				if ($wrongLanguages !== []) {
					throw new SecurityTxtError($correctValue === null
						? new SecurityTxtPreferredLanguagesWrongLanguageTags($wrongLanguages)
						: new SecurityTxtPreferredLanguagesWrongLanguageTags($wrongLanguages, $correctValue));
				}
				foreach ($preferredLanguages->getLanguages() as $key => $value) {
					if (preg_match(self::CZECH_MISTAKE, $value) === 1) {
						throw new SecurityTxtError(new SecurityTxtPreferredLanguagesCommonMistake(
							$key + 1,
							$value,
							$correctValue,
							SecurityTxtPreferredLanguagesCommonMistakeReason::CzechUsesCsNotCz,
						));
					}
				}
			},
		);
	}


	public function getPreferredLanguages(): ?SecurityTxtPreferredLanguages
	{
		return $this->preferredLanguages;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function addAcknowledgments(SecurityTxtAcknowledgments $acknowledgments): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtAcknowledgments => $this->acknowledgments[] = $acknowledgments,
			function () use ($acknowledgments): void {
				$this->checkUri($acknowledgments->getUri(), SecurityTxtAcknowledgmentsNotUri::class, SecurityTxtAcknowledgmentsNotHttps::class, SecurityTxtField::Acknowledgments);
			},
		);
	}


	/**
	 * @return list<SecurityTxtAcknowledgments>
	 */
	public function getAcknowledgments(): array
	{
		return $this->acknowledgments;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function addHiring(SecurityTxtHiring $hiring): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtHiring => $this->hiring[] = $hiring,
			function () use ($hiring): void {
				$this->checkUri($hiring->getUri(), SecurityTxtHiringNotUri::class, SecurityTxtHiringNotHttps::class, SecurityTxtField::Hiring);
			},
		);
	}


	/**
	 * @return list<SecurityTxtHiring>
	 */
	public function getHiring(): array
	{
		return $this->hiring;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function addPolicy(SecurityTxtPolicy $policy): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtPolicy => $this->policy[] = $policy,
			function () use ($policy): void {
				$this->checkUri($policy->getUri(), SecurityTxtPolicyNotUri::class, SecurityTxtPolicyNotHttps::class, SecurityTxtField::Policy);
			},
		);
	}


	/**
	 * @return list<SecurityTxtPolicy>
	 */
	public function getPolicy(): array
	{
		return $this->policy;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function addEncryption(SecurityTxtEncryption $encryption): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtEncryption => $this->encryption[] = $encryption,
			function () use ($encryption): void {
				$this->checkUri($encryption->getUri(), SecurityTxtEncryptionNotUri::class, SecurityTxtEncryptionNotHttps::class, SecurityTxtField::Encryption);
			},
		);
	}


	/**
	 * @return list<SecurityTxtEncryption>
	 */
	public function getEncryption(): array
	{
		return $this->encryption;
	}


	/**
	 * @throws SecurityTxtError
	 */
	public function addCsaf(SecurityTxtCsaf $csaf): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtCsaf => $this->csaf[] = $csaf,
			function () use ($csaf): void {
				$this->checkUri($csaf->getUri(), SecurityTxtCsafNotUri::class, SecurityTxtCsafNotHttps::class, SecurityTxtField::Csaf);
				$url = Url::parse($csaf->getUri());
				if ($url === null || !str_ends_with($url->getPath(), '/' . SecurityTxtCsaf::METADATA_FILENAME)) {
					throw new SecurityTxtError(new SecurityTxtCsafWrongFile($csaf->getUri()));
				}
			},
		);
	}


	/**
	 * @return list<SecurityTxtCsaf>
	 */
	public function getCsaf(): array
	{
		return $this->csaf;
	}


	public function setBugBounty(SecurityTxtBugBounty $bugBounty): void
	{
		$this->setFieldValue(
			fn(): SecurityTxtBugBounty => $this->bugBounty = $bugBounty,
			function (): void {
			},
		);
	}


	public function getBugBounty(): ?SecurityTxtBugBounty
	{
		return $this->bugBounty;
	}


	/**
	 * @param callable(): void $setValue
	 * @param callable(): void $validator
	 * @param (callable(): void)|null $warnings
	 * @return void
	 */
	private function setValue(callable $setValue, callable $validator, ?callable $warnings = null): void
	{
		if ($this->validationLevel === SecurityTxtValidationLevel::AllowInvalidValuesSilently) {
			$setValue();
			return;
		}
		if ($this->validationLevel === SecurityTxtValidationLevel::AllowInvalidValues) {
			$setValue();
			$validator();
		} else {
			$validator();
			$setValue();
		}
		if ($warnings !== null) {
			$warnings();
		}
	}


	/**
	 * @param callable(): SecurityTxtFieldValue $setValue
	 * @param callable(): void $validator
	 * @param (callable(): void)|null $warnings
	 * @return void
	 */
	private function setFieldValue(callable $setValue, callable $validator, ?callable $warnings = null): void
	{
		$this->setValue(
			function () use ($setValue): void {
				$this->fields[] = $setValue();
			},
			$validator,
			$warnings,
		);
	}


	/**
	 * The tag a value is, or is likely meant to be, the language's English name, `Czech`, or the tag with underscores, `cs_CZ`, with the Czech code put right too
	 */
	private function getLanguageTag(string $value): ?string
	{
		$tag = self::LANGUAGE_NAMES[strtolower($value)] ?? str_replace('_', '-', $value);
		return preg_match(self::LANGUAGE_TAG, $tag) === 1 ? preg_replace(self::CZECH_MISTAKE, 'cs', $tag) : null;
	}


	/**
	 * @param class-string<SecurityTxtAcknowledgmentsNotUri|SecurityTxtCanonicalNotUri|SecurityTxtContactNotUri|SecurityTxtCsafNotUri|SecurityTxtEncryptionNotUri|SecurityTxtHiringNotUri|SecurityTxtPolicyNotUri|SecurityTxtFileLocationNotUri> $notUriError
	 * @param class-string<SecurityTxtAcknowledgmentsNotHttps|SecurityTxtCanonicalNotHttps|SecurityTxtContactNotHttps|SecurityTxtCsafNotHttps|SecurityTxtEncryptionNotHttps|SecurityTxtHiringNotHttps|SecurityTxtPolicyNotHttps|SecurityTxtFileLocationNotHttps> $notHttpsError
	 * @throws SecurityTxtError
	 */
	private function checkUri(string $uri, string $notUriError, string $notHttpsError, ?SecurityTxtField $field = null, bool $fetched = false): void
	{
		new SecurityTxtUriValidator()->validate($uri, $notUriError, $notHttpsError, $field, $fetched, $this->fileLocation);
	}


	/**
	 * @return list<SecurityTxtFieldValue>
	 */
	public function getFields(): array
	{
		return $this->fields;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'fileLocation' => $this->getFileLocation(),
			'fields' => array_map(fn(SecurityTxtFieldValue $value): array => [$value->getField()->value => $value], $this->fields),
			'signatureVerifyResult' => $this->getSignatureVerifyResult(),
		];
	}

}
