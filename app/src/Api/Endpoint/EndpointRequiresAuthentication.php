<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Endpoint;

use Attribute;

/**
 * Marks an API endpoint that requires authentication — enforced by the endpoint itself, only declared by this attribute.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EndpointRequiresAuthentication implements EndpointAccessAttribute
{
}
