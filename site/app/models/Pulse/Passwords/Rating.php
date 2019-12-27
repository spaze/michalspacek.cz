<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use Nette\Database\Context;
use RuntimeException;

class Rating
{

	/** @internal rating grades */
	private const RATING_A = 'A';
	private const RATING_B = 'B';
	private const RATING_C = 'C';
	private const RATING_D = 'D';
	private const RATING_E = 'E';
	private const RATING_F = 'F';

	/** @var Context */
	protected $database;

	/** @var string[] */
	private $slowHashes = [
		'argon2',
		'bcrypt',
		'pbkdf2',
		'scrypt',
	];

	/** @var string[] */
	private $insecure = [
		'plaintext',
		'encrypted',
	];

	/** @var string[] */
	private $visibleDisclosures = [
		'docs',
		'faq',
		'signup-page',
	];

	/** @var string[] */
	private $invisibleDisclosures = [
		'blog',
		'site-independent',
		'facebook-independent',
		'facebook-official',
		'facebook-private',
		'talk',
		'twitter-independent',
		'twitter-official',
		'twitter-private',
		'source-code',
		'changelog',
		'comment',
	];

	/** @var array<string, string> */
	private $rating = [
		self::RATING_A => 'Site uses a slow hashing function, this is disclosed "on-site", in the docs, FAQ, etc.',
		self::RATING_B => 'A slow hashing function is used but such info is "invisible", hidden in a blog post or a talk, or on social media.',
		self::RATING_C => 'Passwords hashed with an unsuitable function but at least they are salted and stretched with multiple iterations.',
		self::RATING_D => 'Inappropriate function used to hash passwords but passwords are salted, at least.',
		self::RATING_E => 'Unsalted passwords hashed with one iteration of unsuitable function.',
		self::RATING_F => 'Passwords stored in plaintext, in their original, readable form, or passwords encrypted instead of hashed.',
	];

	/** @var array<string, string|null> */
	private $recommendations = [
		self::RATING_A => null,
		self::RATING_B => 'Publish storage and hashing info details "visibly":[link:Pulse:PasswordsStorages:Rating#on-site] (e.g. in the docs or FAQ), then let me know.',
		self::RATING_C => 'Start using "&quot;slow&quot; hashes":[link:Pulse:PasswordsStorages:Rating#slow-hashes], don\'t forget to "re-hash existing passwords":[blog:upgrading-existing-password-hashes], publish hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
		self::RATING_D => 'Start using "&quot;slow&quot; hashes":[link:Pulse:PasswordsStorages:Rating#slow-hashes], don\'t forget to "re-hash existing passwords":[blog:upgrading-existing-password-hashes], publish hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
		self::RATING_E => 'Start using "&quot;slow&quot; hashes":[link:Pulse:PasswordsStorages:Rating#slow-hashes], also "re-hash existing passwords":[blog:upgrading-existing-password-hashes] if needed, publish hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
		self::RATING_F => 'Hash passwords using a "&quot;slow&quot; hashing function":[link:Pulse:PasswordsStorages:Rating#slow-hashes], publish storage and hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
	];


	/**
	 * Calculate site rating.
	 *
	 * A - slow hashes, doc
	 * B - slow hashes, fb/twitter/src
	 * C - other hashes, salted, stretched
	 * D - other hashes, salted
	 * E - plain MD5, SHA-1, SHA-2, SHA-3, encrypted
	 * F - plaintext
	 *
	 * @param Algorithm $algo
	 * @return string 'A'-'F'
	 */
	public function get(Algorithm $algo): string
	{
		if (in_array($algo->alias, $this->slowHashes, true)) {
			foreach ($this->visibleDisclosures as $disclosure) {
				if (isset($algo->disclosureTypes[$disclosure])) {
					return self::RATING_A;
				}
			}
			foreach ($this->invisibleDisclosures as $disclosure) {
				if (isset($algo->disclosureTypes[$disclosure])) {
					return self::RATING_B;
				}
			}
			throw new RuntimeException(sprintf('Invalid combination of algo (%s) and disclosures (%s)', $algo->alias, implode(', ', array_keys($algo->disclosureTypes))));
		} elseif ($algo->salted && $algo->stretched) {
			return self::RATING_C;
		} elseif ($algo->salted) {
			return self::RATING_D;
		} elseif (!in_array($algo->alias, $this->insecure, true)) {
			return self::RATING_E;
		} else {
			return self::RATING_F;
		}
	}


	/**
	 * Get recommendation for rating.
	 *
	 * @param string $rating
	 * @return string|null
	 */
	public function getRecommendation(string $rating): ?string
	{
		return $this->recommendations[$rating];
	}


	/**
	 * Check whether the rating represents secure storage.
	 *
	 * @param string $rating
	 * @return boolean
	 */
	public function isSecureStorage(string $rating): bool
	{
		return in_array($rating, [self::RATING_A, self::RATING_B]);
	}


	/**
	 * Get rating guide.
	 *
	 * @return string[]
	 */
	public function getRatingGuide(): array
	{
		return $this->rating;
	}


	/**
	 * Get array of slow hashes' aliases
	 *
	 * @return string[]
	 */
	public function getSlowHashes(): array
	{
		return $this->slowHashes;
	}


	/**
	 * Get array of invisible disclosures' aliases
	 *
	 * @return string[]
	 */
	public function getInvisibleDisclosures(): array
	{
		return $this->invisibleDisclosures;
	}


	/**
	 * Get array of visible disclosures' aliases
	 *
	 * @return string[]
	 */
	public function getVisibleDisclosures(): array
	{
		return $this->visibleDisclosures;
	}

}
