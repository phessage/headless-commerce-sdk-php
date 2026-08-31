<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;
final class ProblemException extends \RuntimeException {
    public function __construct(public readonly int $status, public readonly string $type, public readonly ?string $requestId, string $message) { parent::__construct($message); }
}
