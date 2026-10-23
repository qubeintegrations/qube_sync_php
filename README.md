# qube_sync_php

> **Unsupported.** This library is an unfinished proof of concept and isn't maintained.
> Don't use it to verify QuBe Sync webhooks: it signs the body alone instead of
> `"<t>.<raw body>"`, so it can't verify a real delivery, it compares signatures with `===`
> rather than in constant time, it keeps only the last `v1` signature, and it accepts
> timestamps up to 500 seconds old or any time in the future.
>
> To verify a webhook in PHP, follow the webhook guide at https://docs.qubesync.com:
> HMAC-SHA256 of `"<t>.<raw body>"` with your webhook secret, compared with
> `hash_equals` against every `v1` in `X-QUBE-Signature`, and a timestamp within 300 seconds
> of now, either way.

PHP wrapper for QubeSync API
