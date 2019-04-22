<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\Application\UI\Form;

/**
 * Abstract form with *no* CSRF protection.
 */
abstract class UnprotectedForm extends Form
{
}
