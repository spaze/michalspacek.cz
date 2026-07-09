<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Endpoint;

/**
 * Implemented by attributes that declare an API endpoint's access. EndpointAccess::isDeclared() matches any
 * implementer via IS_INSTANCEOF, so a new access attribute needs no change to the check or base presenter.
 */
interface EndpointAccessAttribute
{
}
