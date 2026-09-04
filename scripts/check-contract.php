<?php declare(strict_types=1);

$contract = __DIR__.'/../contracts/headless-commerce-v1.openapi.yaml';
$manifest = __DIR__.'/../contracts/headless-commerce-v1.openapi.sha256';
$expected = strtok(trim((string) file_get_contents($manifest)), " \t");
$actual = hash_file('sha256', $contract);
if ($actual !== $expected) throw new RuntimeException('OpenAPI snapshot digest changed; review and update it deliberately.');
$source = (string) file_get_contents($contract);
foreach (['HeadlessProblem:', 'CustomerAuthenticationResponse:', 'CustomerSessionResponse:', 'CustomerAddressResponse:', 'CustomerOrderPageResponse:', 'CustomerReturnResponse:'] as $schema) {
    if (!str_contains($source, $schema)) throw new RuntimeException("Required contract schema missing: {$schema}");
}
foreach (['requestId', 'code', 'errors', 'fields'] as $field) {
    if (!str_contains($source, "        {$field}:")) throw new RuntimeException("Headless problem field missing: {$field}");
}
echo "Reviewed OpenAPI contract {$actual}\n";
