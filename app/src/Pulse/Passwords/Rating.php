<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Passwords\Storage\StorageAlgorithm;
use RuntimeException;

final class Rating
{

	private const array SLOW_HASHES = [
		'argon2',
		'bcrypt',
		'pbkdf2',
		'scrypt',
	];

	private const array INSECURE = [
		'plaintext',
		'encrypted',
	];

	private const array VISIBLE_DISCLOSURES = [
		'docs',
		'faq',
		'signup-page',
	];

	private const array INVISIBLE_DISCLOSURES = [
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

	private const array RATING = [
		RatingGrade::A->name => 'Site uses a slow hashing function, this is disclosed "on-site", in the docs, FAQ, etc.',
		RatingGrade::B->name => 'A slow hashing function is used but such info is "invisible", hidden in a blog post or a talk, or on social media.',
		RatingGrade::C->name => 'Passwords hashed with an unsuitable function but at least they are salted and stretched with multiple iterations.',
		RatingGrade::D->name => 'Inappropriate function used to hash passwords but passwords are salted, at least.',
		RatingGrade::E->name => 'Unsalted passwords hashed with one iteration of unsuitable function.',
		RatingGrade::F->name => 'Passwords stored in plaintext, in their original, readable form, or passwords encrypted instead of hashed.',
	];

	private const array RECOMMENDATIONS = [
		RatingGrade::A->name => null,
		RatingGrade::B->name => 'Publish storage and hashing info details "visibly":[link:Pulse:PasswordsStorages:Rating#on-site] (e.g. in the docs or FAQ), then let me know.',
		RatingGrade::C->name => 'Start using "&quot;slow&quot; hashes":[link:Pulse:PasswordsStorages:Rating#slow-hashes], don\'t forget to "re-hash existing passwords":[blog:upgrading-existing-password-hashes], publish hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
		RatingGrade::D->name => 'Start using "&quot;slow&quot; hashes":[link:Pulse:PasswordsStorages:Rating#slow-hashes], don\'t forget to "re-hash existing passwords":[blog:upgrading-existing-password-hashes], publish hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
		RatingGrade::E->name => 'Start using "&quot;slow&quot; hashes":[link:Pulse:PasswordsStorages:Rating#slow-hashes], also "re-hash existing passwords":[blog:upgrading-existing-password-hashes] if needed, publish hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
		RatingGrade::F->name => 'Hash passwords using a "&quot;slow&quot; hashing function":[link:Pulse:PasswordsStorages:Rating#slow-hashes], publish storage and hashing info "visibly":[link:Pulse:PasswordsStorages:Rating#on-site], then let me know.',
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
	 */
	public function get(StorageAlgorithm $algo): RatingGrade
	{
		if (in_array($algo->getAlias(), self::SLOW_HASHES, true)) {
			foreach (self::VISIBLE_DISCLOSURES as $disclosure) {
				if ($algo->hasDisclosureType($disclosure)) {
					return RatingGrade::A;
				}
			}
			foreach (self::INVISIBLE_DISCLOSURES as $disclosure) {
				if ($algo->hasDisclosureType($disclosure)) {
					return RatingGrade::B;
				}
			}
			throw new RuntimeException(sprintf('Invalid combination of algo (%s) and disclosures (%s)', $algo->getAlias(), implode(', ', $algo->getDisclosureTypes())));
		} elseif ($algo->isSalted() && $algo->isStretched()) {
			return RatingGrade::C;
		} elseif ($algo->isSalted()) {
			return RatingGrade::D;
		} elseif (!in_array($algo->getAlias(), self::INSECURE, true)) {
			return RatingGrade::E;
		} else {
			return RatingGrade::F;
		}
	}


	/**
	 * Get recommendation for rating.
	 */
	public function getRecommendation(RatingGrade $rating): ?string
	{
		return self::RECOMMENDATIONS[$rating->name];
	}


	/**
	 * Check whether the rating represents secure storage.
	 */
	public function isSecureStorage(RatingGrade $rating): bool
	{
		return in_array($rating, [RatingGrade::A, RatingGrade::B]);
	}


	/**
	 * Get ratings.
	 *
	 * @return array<string, string> lowercase grade => uppercase grade
	 */
	public function getRatings(): array
	{
		$ratings = [];
		foreach (RatingGrade::cases() as $ratingGrade) {
			$ratings[strtolower($ratingGrade->name)] = $ratingGrade->name;
		}
		return $ratings;
	}


	/**
	 * Get rating guide.
	 *
	 * @return string[]
	 */
	public function getRatingGuide(): array
	{
		return self::RATING;
	}


	/**
	 * Get array of slow hashes' aliases
	 *
	 * @return string[]
	 */
	public function getSlowHashes(): array
	{
		return self::SLOW_HASHES;
	}


	/**
	 * Get array of invisible disclosures' aliases
	 *
	 * @return string[]
	 */
	public function getInvisibleDisclosures(): array
	{
		return self::INVISIBLE_DISCLOSURES;
	}


	/**
	 * Get array of visible disclosures' aliases
	 *
	 * @return string[]
	 */
	public function getVisibleDisclosures(): array
	{
		return self::VISIBLE_DISCLOSURES;
	}

}
