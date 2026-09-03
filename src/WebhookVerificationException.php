<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;
final class WebhookVerificationException extends \RuntimeException
{
    public function __construct(public readonly string $reason, string $message) { parent::__construct($message); }
}
