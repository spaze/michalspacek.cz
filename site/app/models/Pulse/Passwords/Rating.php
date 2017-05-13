<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

/**
 * Pulse passwords rating service.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class Rating
{

	/** @internal rating grades */
	private const RATING_A = 'A';
	private const RATING_B = 'B';
	private const RATING_C = 'C';
	private const RATING_D = 'D';
	private const RATING_E = 'E';
	private const RATING_F = 'F';

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var array */
	private $slowHashes = [
		'argon2',
		'bcrypt',
		'pbkdf2',
		'scrypt',
	];

	/** @var array */
	private $plaintext = [
		'plaintext',
	];

	/** @var array */
	private $visibleDisclosures = [
		'docs',
		'faq',
		'signup-page',
	];

	/** @var array */
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
	 * @param \MichalSpacekCz\Pulse\Passwords\Algorithm $algo
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
			throw new \RuntimeException(sprintf('Invalid combination of algo (%s) and disclosures (%s)', $algo->alias, implode(', ', array_keys($algo->disclosureTypes))));
		} elseif ($algo->salted && $algo->stretched) {
			return self::RATING_C;
		} elseif ($algo->salted) {
			return self::RATING_D;
		} elseif (!in_array($algo->alias, $this->plaintext, true)) {
			return self::RATING_E;
		} else {
			return self::RATING_F;
		}
	}


	/**
	 * Get rating guide.
	 *
	 * @return string[]
	 */
	public function getRatingGuide(): array
	{
		return [
			self::RATING_A => 'Site uses a slow hashing function, this is disclosed "on-site", in the docs, FAQ, etc.',
			self::RATING_B => 'A slow hashing function is used but such info is "invisible", hidden in a blog post or a talk, or on social media.',
			self::RATING_C => 'Passwords hashed with an unsuitable function but at least they are salted and stretched with multiple iterations.',
			self::RATING_D => 'Inappropriate function used to hash passwords but passwords are salted, at least.',
			self::RATING_E => 'Unsalted passwords hashed with one iteration of unsuitable function, or passwords encrypted instead of hashed.',
			self::RATING_F => 'Passwords stored in plaintext, in their original, readable form.',
		];
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
