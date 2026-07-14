# Introduction

qrm.sg — Dynamic QR Code Management API. Create, manage and track QR codes programmatically.

<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>

    ## Authentication

    All endpoints except `GET /api` and `GET /api/qr-types` require a Bearer token.

    **API access** requires a Business plan or Pro plan with the API add-on (+1 €/month).
    Create tokens in your account at `/account/api-tokens`.

    ## Rate Limits

    - **Pro:** 60 requests/minute
    - **Business:** 300 requests/minute

    Rate limit info is sent in `X-RateLimit-*` headers on every response.

