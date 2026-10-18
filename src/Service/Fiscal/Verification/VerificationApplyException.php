<?php

declare(strict_types=1);

namespace App\Service\Fiscal\Verification;

final class VerificationApplyException extends \RuntimeException
{
    public function __construct(string $message, public readonly ?VerificationResult $result = null)
    {
        parent::__construct($message);
    }
}
