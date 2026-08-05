<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Format;

use JsonException;
use ParagonIE\HiddenString\HiddenString;
use SensitiveParameter;
use SodiumException;
use Spaze\Encryption\Exceptions\FormatMarkerMismatchException;
use Spaze\Encryption\Exceptions\InvalidCipherTextFormatException;
use Spaze\Encryption\Exceptions\InvalidKeyEncodingException;
use Spaze\Encryption\Exceptions\InvalidKeyIdException;
use Spaze\Encryption\Exceptions\InvalidKeyLengthException;
use Spaze\Encryption\Exceptions\InvalidKeyPrefixException;
use Spaze\Encryption\Exceptions\InvalidKeyRoleException;
use Spaze\Encryption\Exceptions\InvalidNumberOfComponentsException;
use Spaze\Encryption\Exceptions\MissingKeyPrefixException;
use Spaze\Encryption\Exceptions\UnknownFormatMarkerException;
use function count;
use function explode;
use function in_array;
use function json_encode;

/**
 * @internal Shared key decoding and encrypted output formatting, not part of the public API
 */
trait KeyEnvelope
{

	private const KEY_CIPHERTEXT_SEPARATOR = '$';
	private const KEY_PREFIX_SEPARATOR = '_';


	/**
	 * @param array<array-key, string> $keys key id => key
	 * @return array<string, HiddenString>
	 * @throws InvalidKeyEncodingException
	 * @throws InvalidKeyIdException
	 * @throws InvalidKeyLengthException
	 * @throws InvalidKeyPrefixException
	 * @throws InvalidKeyRoleException
	 * @throws MissingKeyPrefixException
	 */
	private function decodeKeys(#[SensitiveParameter] array $keys, string $keyPrefix, int $expectedLength, ?AsymmetricKeyRole $role = null): array
	{
		$keyPrefix .= self::KEY_PREFIX_SEPARATOR;
		$decodedKeys = [];
		foreach ($keys as $id => $key) {
			$id = (string)$id;
			if ($id === '' || str_contains($id, self::KEY_CIPHERTEXT_SEPARATOR)) {
				throw new InvalidKeyIdException($id, self::KEY_CIPHERTEXT_SEPARATOR);
			}
			if (!str_starts_with($key, $keyPrefix)) {
				if (str_contains($key, self::KEY_PREFIX_SEPARATOR)) {
					throw new InvalidKeyPrefixException($id, $keyPrefix);
				}
				throw new MissingKeyPrefixException($id, $keyPrefix);
			}
			$hexKey = substr($key, strlen($keyPrefix));
			if ($role !== null) {
				$hexKey = $this->stripKeyRole($id, $hexKey, $role);
			}
			try {
				$decodedKey = sodium_hex2bin($hexKey);
			} catch (SodiumException $e) {
				throw new InvalidKeyEncodingException($id, $e);
			}
			if (strlen($decodedKey) !== $expectedLength) {
				throw new InvalidKeyLengthException($id, strlen($decodedKey), $expectedLength);
			}
			$decodedKeys[$id] = new HiddenString($decodedKey);
		}
		return $decodedKeys;
	}


	/**
	 * The role tag between the prefix and the key itself is optional, but when present, it has to match how the key is used.
	 *
	 * @throws InvalidKeyRoleException
	 */
	private function stripKeyRole(string $id, #[SensitiveParameter] string $key, AsymmetricKeyRole $expectedRole): string
	{
		foreach (AsymmetricKeyRole::cases() as $role) {
			if (str_starts_with($key, $role->value . self::KEY_PREFIX_SEPARATOR)) {
				if ($role !== $expectedRole) {
					throw new InvalidKeyRoleException($id, $expectedRole, $role);
				}
				return substr($key, strlen($role->value . self::KEY_PREFIX_SEPARATOR));
			}
		}
		return $key;
	}


	/**
	 * Reads both the marked format and the one without a marker, because data encrypted
	 * before the marker existed has to keep decrypting.
	 *
	 * @return array{0:non-empty-string, 1:non-empty-string|null, 2:non-empty-string}
	 * @throws InvalidCipherTextFormatException
	 * @throws InvalidNumberOfComponentsException
	 */
	private function parseKeyCipherText(string $data): array
	{
		$data = explode(self::KEY_CIPHERTEXT_SEPARATOR, $data);
		$count = count($data);
		if ($count !== 3 && $count !== 4) {
			throw new InvalidNumberOfComponentsException();
		}
		if ($data[0] !== '' || $data[1] === '' || $data[2] === '' || ($count === 4 && $data[3] === '')) {
			throw new InvalidCipherTextFormatException();
		}
		return $count === 3 ? [$data[1], null, $data[2]] : [$data[1], $data[2], $data[3]];
	}


	private function formatKeyCipherText(string $keyId, FormatMarker $marker, string $cipherText): string
	{
		return self::KEY_CIPHERTEXT_SEPARATOR . $keyId . self::KEY_CIPHERTEXT_SEPARATOR . $marker->value . self::KEY_CIPHERTEXT_SEPARATOR . $cipherText;
	}


	/**
	 * The value that ties the encrypted data to its key id and marker: it goes into what the decryption verifies,
	 * so changing the key id or the marker in the stored value makes decryption fail.
	 * The key id and the additional data are Base64-encoded (the URL-safe kind), so the result is the same
	 * no matter what bytes they contain, no JSON character escaping ever kicks in, and the value can be rebuilt
	 * anywhere with plain string formatting. This exact recipe can never change, a changed recipe is a new marker.
	 *
	 * @throws JsonException
	 * @throws SodiumException
	 */
	private function buildBoundAdditionalData(string $keyId, FormatMarker $marker, #[SensitiveParameter] ?string $additionalData = null): string
	{
		$values = [
			'keyId' => sodium_bin2base64($keyId, SODIUM_BASE64_VARIANT_URLSAFE),
			'marker' => $marker->value,
		];
		if ($additionalData !== null) {
			$values['additionalData'] = sodium_bin2base64($additionalData, SODIUM_BASE64_VARIANT_URLSAFE);
		}
		return json_encode($values, JSON_THROW_ON_ERROR);
	}


	/**
	 * Data needs re-encryption when encrypted with an inactive key, or when stored in the older format
	 * without the marker, because re-encrypting adds it.
	 *
	 * @throws FormatMarkerMismatchException
	 * @throws InvalidCipherTextFormatException
	 * @throws InvalidNumberOfComponentsException
	 * @throws UnknownFormatMarkerException
	 */
	private function needsReEncryptMarked(string $data, string $activeKeyId, FormatMarker ...$expectedMarkers): bool
	{
		[$keyId, $marker] = $this->parseKeyCipherText($data);
		$validMarker = $this->checkFormatMarker($marker, ...$expectedMarkers);
		return $keyId !== $activeKeyId || $validMarker === null;
	}


	/**
	 * No marker is fine, that's data from before the marker existed; a marker that is present
	 * has to be one of the expected ones. Returns the validated marker, so that the callers build
	 * the verified value from what the stored data actually says, not from what they happen to expect:
	 * the two are the same today, but only the former stays correct when a method accepts more markers.
	 *
	 * @throws FormatMarkerMismatchException
	 * @throws UnknownFormatMarkerException
	 */
	private function checkFormatMarker(?string $marker, FormatMarker ...$expectedMarkers): ?FormatMarker
	{
		if ($marker === null) {
			return null;
		}
		$actualMarker = FormatMarker::tryFrom($marker);
		if ($actualMarker === null) {
			throw new UnknownFormatMarkerException($marker);
		}
		if (!in_array($actualMarker, $expectedMarkers, true)) {
			throw new FormatMarkerMismatchException($actualMarker);
		}
		return $actualMarker;
	}

}
