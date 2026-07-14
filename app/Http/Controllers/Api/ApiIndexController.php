<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiIndexController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => 'qrm.sg API',
            'version' => '1.0',
            'authentication' => [
                'type' => 'Bearer Token (Sanctum)',
                'header' => 'Authorization: Bearer <token>',
                'description' => 'Create tokens via Account → API Tokens. Tokens are scoped (qr:read, qr:write).',
            ],
            'endpoints' => [
                [
                    'method' => 'GET',
                    'path' => '/api',
                    'description' => 'API endpoint index',
                    'auth_required' => false,
                    'example_response' => ['name' => 'qrm.sg API', 'version' => '1.0'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/user',
                    'description' => 'Authenticated user profile',
                    'auth_required' => true,
                    'example_response' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'plan' => 'pro'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-types',
                    'description' => 'All QR types with field schemas',
                    'auth_required' => false,
                    'example_response' => [['type' => 'url', 'label' => 'URL', 'fields' => [['name' => 'url', 'type' => 'text', 'required' => true]]]],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr/{code}/download.svg',
                    'description' => 'Download QR code as SVG',
                    'auth_required' => false,
                    'example' => 'curl https://qrm.sg/api/qr/ABC123/download.svg -o qr.svg',
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr/{code}/download.png',
                    'description' => 'Download QR code as PNG',
                    'auth_required' => false,
                    'example' => 'curl https://qrm.sg/api/qr/ABC123/download.png -o qr.png',
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/billing/webhook',
                    'description' => 'Stripe billing webhook (Stripe-signed)',
                    'auth_required' => false,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes/free-tier-status',
                    'description' => 'Free tier status',
                    'auth_required' => true,
                    'example_response' => ['tier' => 'free', 'active_count' => 3, 'limit' => 10, 'remaining' => 7],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes/feature-status',
                    'description' => 'Feature gate status',
                    'auth_required' => true,
                    'example_response' => ['can_use_password_protection' => true, 'can_use_custom_alias' => true],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes',
                    'description' => 'List your QR codes',
                    'auth_required' => true,
                    'scopes' => ['qr:read'],
                    'example' => 'curl -H "Authorization: Bearer <token>" https://qrm.sg/api/qr-codes',
                    'example_response' => ['data' => [['id' => 1, 'title' => 'My QR', 'type' => 'url', 'status' => 'active', 'scan_count' => 42]]],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/qr-codes',
                    'description' => 'Create a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:write'],
                    'example' => 'curl -X POST -H "Authorization: Bearer <token>" -H "Content-Type: application/json" -d \'{"title":"My QR","type":"url","content":{"url":"https://example.com"}}\' https://qrm.sg/api/qr-codes',
                    'example_response' => ['id' => 42, 'title' => 'My QR', 'type' => 'url', 'route' => ['code' => 'ABC123', 'url' => 'https://qrm.sg/r/ABC123']],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes/{qr_code}',
                    'description' => 'Show a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:read'],
                    'example_response' => ['id' => 42, 'title' => 'My QR', 'type' => 'url', 'content' => ['url' => 'https://example.com'], 'status' => 'active', 'scan_count' => 42],
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/qr-codes/{qr_code}',
                    'description' => 'Update a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:write'],
                    'example' => 'curl -X PUT -H "Authorization: Bearer <token>" -H "Content-Type: application/json" -d \'{"title":"Updated Title"}\' https://qrm.sg/api/qr-codes/42',
                ],
                [
                    'method' => 'PATCH',
                    'path' => '/api/qr-codes/{qr_code}',
                    'description' => 'Partially update a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:write'],
                ],
                [
                    'method' => 'DELETE',
                    'path' => '/api/qr-codes/{qr_code}',
                    'description' => 'Delete a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:write'],
                    'example' => 'curl -X DELETE -H "Authorization: Bearer <token>" https://qrm.sg/api/qr-codes/42',
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/billing/checkout',
                    'description' => 'Create a billing checkout session',
                    'auth_required' => true,
                    'example_response' => ['url' => 'https://checkout.stripe.com/...'],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/billing/portal',
                    'description' => 'Create a billing portal session',
                    'auth_required' => true,
                    'example_response' => ['url' => 'https://billing.stripe.com/...'],
                ],
            ],
        ]);
    }
}
