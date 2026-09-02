<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;
final class ProblemException extends \RuntimeException {
    /** @param array{limit:?int,remaining:?int,reset:?int,retryAfter:?string} $rateLimit */
    public function __construct(public readonly int $status, public readonly string $type, public readonly ?string $requestId, string $message, public readonly array $rateLimit = ['limit'=>null,'remaining'=>null,'reset'=>null,'retryAfter'=>null]) { parent::__construct($message); }
}
