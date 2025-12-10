<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt;

use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fields\SecurityTxtAcknowledgments;
use Spaze\SecurityTxt\Fields\SecurityTxtCanonical;
use Spaze\SecurityTxt\Fields\SecurityTxtContact;
use Spaze\SecurityTxt\Fields\SecurityTxtEncryption;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
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
use Spaze\SecurityTxt\Violations\SecurityTxtPreferredLanguagesEmpty;
use Spaze\SecurityTxt\Violations\SecurityTxtPreferredLanguagesWrongLanguageTags;

final class SecurityTxt implements JsonSerializable
{

	private ?string $fileLocation = null;
	private ?SecurityTxtExpires $expires = null;
	private ?SecurityTxtSignatureVerifyResult $signatureVerifyResult = null;
	private ?SecurityTxtPreferredLanguages $preferredLanguages = null;

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
	 * @var list<SecurityTxtFieldValue>
	 */
	private array $orderedFields = [];


	public function __construct(
		private readonly SecurityTxtValidationLevel $validationLevel = SecurityTxtValidationLevel::NoInvalidValues,
	) {
	}


	public function setFileLocation(string $fileLocation): void
	{
		$this->setValue(
			function () use ($fileLocation): void {
				$this->fileLocation = $fileLocation;
			},
			function () use ($fileLocation): void {
				$this->checkUri($fileLocation, SecurityTxtFileLocationNotUri::class, SecurityTxtFileLocationNotHttps::class);
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
			function () use ($expires): SecurityTxtExpires {
				return $this->expires = $expires;
			},
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
			function () use ($canonical): SecurityTxtCanonical {
				return $this->canonical[] = $canonical;
			},
			function () use ($canonical): void {
				$this->checkUri($canonical->getUri(), SecurityTxtCanonicalNotUri::class, SecurityTxtCanonicalNotHttps::class);
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
			function () use ($contact): SecurityTxtContact {
				return $this->contact[] = $contact;
			},
			function () use ($contact): void {
				$this->checkUri($contact->getUri(), SecurityTxtContactNotUri::class, SecurityTxtContactNotHttps::class);
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
			function () use ($preferredLanguages): SecurityTxtPreferredLanguages {
				return $this->preferredLanguages = $preferredLanguages;
			},
			function () use ($preferredLanguages): void {
				if ($preferredLanguages->getLanguages() === []) {
					throw new SecurityTxtError(new SecurityTxtPreferredLanguagesEmpty());
				}
				$wrongLanguages = [];
				foreach ($preferredLanguages->getLanguages() as $key => $value) {
					if (preg_match('/^([a-z]{2,3}(-[a-z0-9]+)*|[xi]-[a-z0-9]+)$/i', $value) !== 1) {
						$wrongLanguages[$key + 1] = $value;
					}
				}
				if ($wrongLanguages !== []) {
					throw new SecurityTxtError(new SecurityTxtPreferredLanguagesWrongLanguageTags($wrongLanguages));
				}
				foreach ($preferredLanguages->getLanguages() as $key => $value) {
					if (preg_match('/^cz-?/i', $value) === 1) {
						throw new SecurityTxtError(new SecurityTxtPreferredLanguagesCommonMistake(
							$key + 1,
							$value,
							preg_replace('/^cz$|cz(-)/i', 'cs$1', $value),
							'the code for Czech language is %s, not %s',
							['cs', 'cz'],
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
			function () use ($acknowledgments): SecurityTxtAcknowledgments {
				return $this->acknowledgments[] = $acknowledgments;
			},
			function () use ($acknowledgments): void {
				$this->checkUri($acknowledgments->getUri(), SecurityTxtAcknowledgmentsNotUri::class, SecurityTxtAcknowledgmentsNotHttps::class);
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
			function () use ($hiring): SecurityTxtHiring {
				return $this->hiring[] = $hiring;
			},
			function () use ($hiring): void {
				$this->checkUri($hiring->getUri(), SecurityTxtHiringNotUri::class, SecurityTxtHiringNotHttps::class);
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
			function () use ($policy): SecurityTxtPolicy {
				return $this->policy[] = $policy;
			},
			function () use ($policy): void {
				$this->checkUri($policy->getUri(), SecurityTxtPolicyNotUri::class, SecurityTxtPolicyNotHttps::class);
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
			function () use ($encryption): SecurityTxtEncryption {
				return $this->encryption[] = $encryption;
			},
			function () use ($encryption): void {
				$this->checkUri($encryption->getUri(), SecurityTxtEncryptionNotUri::class, SecurityTxtEncryptionNotHttps::class);
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
	 * @param callable(): void $setValue
	 * @param callable(): void $validator
	 * @return void
	 */
	private function setValue(callable $setValue, callable $validator): void
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
				$this->orderedFields[] = $setValue();
			},
			$validator,
		);
		if ($warnings !== null) {
			$warnings();
		}
	}


	/**
	 * @param class-string<SecurityTxtAcknowledgmentsNotUri|SecurityTxtCanonicalNotUri|SecurityTxtContactNotUri|SecurityTxtEncryptionNotUri|SecurityTxtHiringNotUri|SecurityTxtPolicyNotUri|SecurityTxtFileLocationNotUri> $notUriError
	 * @param class-string<SecurityTxtAcknowledgmentsNotHttps|SecurityTxtCanonicalNotHttps|SecurityTxtContactNotHttps|SecurityTxtEncryptionNotHttps|SecurityTxtHiringNotHttps|SecurityTxtPolicyNotHttps|SecurityTxtFileLocationNotHttps> $notHttpsError
	 * @throws SecurityTxtError
	 */
	private function checkUri(string $uri, string $notUriError, string $notHttpsError): void
	{
		$scheme = parse_url($uri, PHP_URL_SCHEME);
		if ($scheme === false || $scheme === null) {
			throw new SecurityTxtError(new $notUriError($uri));
		}
		if (strtolower($scheme) === 'http') {
			throw new SecurityTxtError(new $notHttpsError($uri));
		}
	}


	/**
	 * @return list<SecurityTxtFieldValue>
	 */
	public function getOrderedFields(): array
	{
		return $this->orderedFields;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'fileLocation' => $this->getFileLocation(),
			'expires' => $this->getExpires(),
			'signatureVerifyResult' => $this->getSignatureVerifyResult(),
			'preferredLanguages' => $this->getPreferredLanguages(),
			'canonical' => $this->getCanonical(),
			'contact' => $this->getContact(),
			'acknowledgments' => $this->getAcknowledgments(),
			'hiring' => $this->getHiring(),
			'policy' => $this->getPolicy(),
			'encryption' => $this->getEncryption(),
		];
	}

}
