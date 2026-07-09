<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Endpoint;

use Attribute;

/**
 * Marks a presenter under the Api module that is not a routed endpoint (e.g. a framework error presenter),
 * so it is exempt from the access declaration real endpoints must carry.
 *
 * Deliberately not an EndpointAccessAttribute, so the gate can't mistake it for a real one.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class NotAnEndpoint
{
}
