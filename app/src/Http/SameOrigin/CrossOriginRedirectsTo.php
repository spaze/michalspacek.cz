<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SameOrigin;

use Attribute;
use Nette\Application\Attributes\Requires;

/**
 * Requires same origin and sends a cross-origin request to the given destination instead of running the action.
 *
 * Extends Requires with a preset sameOrigin: true, like Nette's own CrossOrigin presets false, so Nette
 * enforces the check itself. A failed check normally redirects back to the same action, a recovery for
 * same-site requests that don't have the `_nss` cookie yet, but a loop for a genuinely cross-origin
 * request, so Www\BasePresenter::detectedCsrf() sends those to the destination instead.
 *
 * The destination must be an absolute action, like ':Admin:Homepage:', and must not require same origin
 * itself, or the loop would only move there.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class CrossOriginRedirectsTo extends Requires
{

	public function __construct(
		public readonly string $destination,
	) {
		parent::__construct(sameOrigin: true);
	}

}
