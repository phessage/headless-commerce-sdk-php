# Architecture

The SDK is framework-neutral and depends only on PHP. Transport is injectable for PSR/framework adapters and deterministic tests. Its public model follows the reviewed `contracts/headless-commerce-v1.openapi.yaml` snapshot. The SDK intentionally returns associative arrays rather than duplicating 1Ecomm domain classes; the pinned contract is the field/type authority, and the client preserves public problem metadata without calculating commerce decisions.

The Composer release archive excludes the development `vendor/` tree and repository automation metadata. Its smoke gate rejects any bundled vendor dependency, an archive over 1 MB, or omission of the license, OpenAPI snapshot or client entry point before performing a clean `--no-dev` install.
