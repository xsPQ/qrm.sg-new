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
            'endpoints' => [
                [
                    'method' => 'GET',
                    'path' => '/api',
                    'description' => 'API endpoint index',
                    'auth_required' => false,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/user',
                    'description' => 'Authenticated user profile',
                    'auth_required' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-types',
                    'description' => 'All QR types with field schemas',
                    'auth_required' => false,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr/{code}/download.svg',
                    'description' => 'Download QR code as SVG',
                    'auth_required' => false,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr/{code}/download.png',
                    'description' => 'Download QR code as PNG',
                    'auth_required' => false,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/billing/webhook',
                    'description' => 'Stripe billing webhook',
                    'auth_required' => false,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes/free-tier-status',
                    'description' => 'Free tier status',
                    'auth_required' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes/feature-status',
                    'description' => 'Feature gate status',
                    'auth_required' => true,
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes',
                    'description' => 'List your QR codes',
                    'auth_required' => true,
                    'scopes' => ['qr:read'],
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/qr-codes',
                    'description' => 'Create a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:write'],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/qr-codes/{qr_code}',
                    'description' => 'Show a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:read'],
                ],
                [
                    'method' => 'PUT',
                    'path' => '/api/qr-codes/{qr_code}',
                    'description' => 'Update a QR code',
                    'auth_required' => true,
                    'scopes' => ['qr:write'],
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
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/billing/checkout',
                    'description' => 'Create a billing checkout session',
                    'auth_required' => true,
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/billing/portal',
                    'description' => 'Create a billing portal session',
                    'auth_required' => true,
                ],
            ],
        ]);
    }
}
