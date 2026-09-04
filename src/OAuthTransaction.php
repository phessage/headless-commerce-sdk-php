<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;

final readonly class OAuthTransaction
{
    public function __construct(
        public string $state,
        public string $codeVerifier,
        public string $codeChallenge,
    ) {}

    public static function create(): self
    {
        $verifier = self::base64Url(random_bytes(32));
        return new self(
            self::base64Url(random_bytes(32)),
            $verifier,
            self::base64Url(hash('sha256', $verifier, true)),
        );
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
