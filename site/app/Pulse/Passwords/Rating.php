<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use RuntimeException;

class Rating
{

	/** @var string[] */
	private array $slowHashes = [
		'argon2',
		'bcrypt',
		'pbkdf2',
		'scrypt',
	];

	/** @var string[] */
	private array $insecure = [
		'plaintext',
		'encrypted',
	];

	/** @var string[] */
	private array $visibleDisclosures = [
		'docs',
		'faq',
		'signup-page',
	];

	/** @var string[] */
	private array $invisibleDisclosures = [
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
	private array $rating = [
		RatingGrade::A->name => 'Site uses a slow hashing function, this is disclosed "on-site", in the docs, FAQ, etc.',
		RatingGrade::B->name => 'A slow hashing function is used but such info is "invisible", hidden in a blog post or a talk, or on social media.',
		RatingGrade::C->name => 'Passwords hashed with an unsuitable function but at least they are salted and stretched with multiple iterations.',
		RatingGrade::D->name => 'Inappropriate function used to hash passwords but passwords are salted, at least.',
		RatingGrade::E->name => 'Unsalted passwords hashed with one iteration of unsuitable function.',
		RatingGrade::F->name => 'Passwords stored in plaintext, in their original, readable form, or passwords encrypted instead of hashed.',
	];

	/** @var array<string, string|null> */
	private array $recommendations = [
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
	public function get(Algorithm $algo): RatingGrade
	{
		if (in_array($algo->getAlias(), $this->slowHashes, true)) {
			foreach ($this->visibleDisclosures as $disclosure) {
				if ($algo->hasDisclosureType($disclosure)) {
					return RatingGrade::A;
				}
			}
			foreach ($this->invisibleDisclosures as $disclosure) {
				if ($algo->hasDisclosureType($disclosure)) {
					return RatingGrade::B;
				}
			}
			throw new RuntimeException(sprintf('Invalid combination of algo (%s) and disclosures (%s)', $algo->getAlias(), implode(', ', $algo->getDisclosureTypes())));
		} elseif ($algo->isSalted() && $algo->isStretched()) {
			return RatingGrade::C;
		} elseif ($algo->isSalted()) {
			return RatingGrade::D;
		} elseif (!in_array($algo->getAlias(), $this->insecure, true)) {
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
		return $this->recommendations[$rating->name];
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
