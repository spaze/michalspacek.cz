<?php
namespace MichalSpacekCz\Pulse\Passwords;

/**
 * Pulse passwords rating service.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class Rating
{

	/** @internal algo type */
	const ALGO_ARGON2 = 'argon2';
	const ALGO_BCRYPT = 'bcrypt';
	const ALGO_PBKDF2 = 'pbkdf2';
	const ALGO_SCRYPT = 'scrypt';
	const ALGO_PLAINTEXT = 'plaintext';

	/** @internal disclosure type */
	const DISCLO_BLOG = 'blog';
	const DISCLO_DOCS = 'docs';
	const DISCLO_FACEBOOK_INDEPENDENT = 'facebook-independent';
	const DISCLO_FACEBOOK_OFFICIAL = 'facebook-official';
	const DISCLO_FACEBOOK_PRIVATE = 'facebook-private';
	const DISCLO_TALK = 'talk';
	const DISCLO_TWITTER_INDEPENDENT = 'twitter-independent';
	const DISCLO_TWITTER_OFFICIAL = 'twitter-official';
	const DISCLO_TWITTER_PRIVATE = 'twitter-private';


	/**
	 * Calculate site rating.
	 *
	 * A - slow hashes, doc
	 * B - slow hashes, fb/twitter/src
	 * C - other hashes, salted, stretched
	 * D - other hashes, salted
	 * E - plain MD5, SHA-1, SHA-2, SHA-3
	 * F - plaintext
	 *
	 * @param \MichalSpacekCz\Pulse\Passwords\Algorithm $algo
	 * @return string 'A'-'F'
	 */
	public function get(Algorithm $algo)
	{
		if (in_array($algo->alias, $this->getSlowHashes())) {
			if (isset($algo->disclosureTypes[self::DISCLO_DOCS])) {
				return 'A';
			} else {
				foreach ($this->getInvisibleDisclosures() as $disclosure) {
					if (isset($algo->disclosureTypes[$disclosure])) {
						return 'B';
					}
				}
				throw new \RuntimeException(sprintf('Invalid combination of algo (%s) and disclosures (%s)', $algo->alias, implode(', ', $algo->disclosureTypes)));
			}
		} elseif ($algo->salted && $algo->stretched) {
			return 'C';
		} elseif ($algo->salted) {
			return 'D';
		} elseif ($algo->alias !== self::ALGO_PLAINTEXT) {
			return 'E';
		} else {
			return 'F';
		}
	}


	/**
	 * Get "slow" hashes.
	 *
	 * @return array
	 */
	private function getSlowHashes()
	{
		return [
			self::ALGO_ARGON2,
			self::ALGO_BCRYPT,
			self::ALGO_PBKDF2,
			self::ALGO_SCRYPT,
		];
	}


	/**
	 * Get "invisible" disclosures.
	 *
	 * @return array
	 */
	private function getInvisibleDisclosures()
	{
		return [
			self::DISCLO_BLOG,
			self::DISCLO_FACEBOOK_INDEPENDENT,
			self::DISCLO_FACEBOOK_OFFICIAL,
			self::DISCLO_FACEBOOK_PRIVATE,
			self::DISCLO_TALK,
			self::DISCLO_TWITTER_INDEPENDENT,
			self::DISCLO_TWITTER_OFFICIAL,
			self::DISCLO_TWITTER_PRIVATE,
		];
	}

}
