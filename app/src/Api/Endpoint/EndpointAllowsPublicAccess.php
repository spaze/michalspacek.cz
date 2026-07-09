<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Endpoint;

use Attribute;

/**
 * Marks an API endpoint intentionally reachable without auth; the deliberate opt-out from the base presenter's default-deny.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EndpointAllowsPublicAccess implements EndpointAccessAttribute
{
}
