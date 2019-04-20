<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

/**
 * Abstract form with *no* CSRF protection.
 */
abstract class UnprotectedForm extends \Nette\Application\UI\Form
{
}
