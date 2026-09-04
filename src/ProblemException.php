<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;
final class ProblemException extends \RuntimeException {
    /** @param array{limit:?int,remaining:?int,reset:?int,retryAfter:?string} $rateLimit @param list<string> $errors @param array<string,mixed> $fields */
    public function __construct(public readonly int $status, public readonly string $type, public readonly ?string $requestId, string $message, public readonly array $rateLimit = ['limit'=>null,'remaining'=>null,'reset'=>null,'retryAfter'=>null], public readonly ?string $problemCode = null, public readonly array $errors = [], public readonly array $fields = []) { parent::__construct($message); }
}
