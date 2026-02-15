# Polymarket EIP-712 Signer Service Contract

PolyTraderX no longer sends hardcoded/unsigned order payloads in live mode.
For non-dry-run orders, it calls an external signer service that performs real
EIP-712 signing using a production-grade signing library.

## Endpoint

- `POST {POLYMARKET_SIGNER_URL}/v1/polymarket/sign-order`
- Optional bearer auth header if `POLYMARKET_SIGNER_API_KEY` is configured.

## Request Body

```json
{
  "wallet_address": "0x...",
  "private_key": "0x...",
  "order_intent": {
    "token_id": "0x...",
    "side": "BUY",
    "price": "0.95",
    "size": "5",
    "order_type": "GTC"
  }
}
```

## Expected Response

```json
{
  "signed_order_payload": {
    "order": {
      "tokenID": "...",
      "price": "...",
      "size": "...",
      "side": "BUY",
      "feeRateBps": "...",
      "nonce": "...",
      "expiration": "...",
      "signatureType": "..."
    },
    "owner": "0x...",
    "orderType": "GTC",
    "signature": "0x..."
  }
}
```

`signed_order_payload` is sent as-is to Polymarket CLOB `/order`.

## Notes

- The signer service must implement Polymarket-compatible EIP-712 typed data.
- Keep private keys encrypted at rest and avoid logging them.
- Restrict service access with network policy and bearer auth.
