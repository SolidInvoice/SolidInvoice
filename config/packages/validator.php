<?php

declare(strict_types=1);

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->validation()
        ->enabled(true)
        ->enableAnnotations(true)
        ->emailValidationMode(Email::VALIDATION_MODE_STRICT);
};
