<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\FeatureNotEntitledException;
use App\Exceptions\FreeTierLimitExceededException;
use App\Exceptions\SlugCollisionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateQrCodeRequest;
use App\Http\Requests\UpdateQrCodeRequest;
use App\Http\Resources\QrCodeResource;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function __construct(
        private QrCodeService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QrCode::class);

        $qrCodes = $this->service->listForUser(
            $request->user(),
            $request->only(['type', 'status']),
            (int) $request->input('per_page', 20),
        );

        return QrCodeResource::collection($qrCodes)->response();
    }

    public function store(CreateQrCodeRequest $request): JsonResponse
    {
        $this->authorize('create', QrCode::class);

        try {
            $qrCode = $this->service->create(
                $request->user(),
                $request->validated(),
            );

            return (new QrCodeResource($qrCode))->response()->setStatusCode(201);
        } catch (FeatureNotEntitledException $e) {
            // Free user tried to set a Pro/Business-only feature (custom alias
            // or password protection, Pflichtenheft §2.1). Surface a 402 with a
            // structured upgrade hint so the Creator/Edit UI can render it.
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'feature_not_entitled',
                'upgrade_required' => true,
                'feature' => $e->feature,
                'plan' => $e->plan,
                'upgrade_hint' => $e->getMessage(),
            ], 402);
        } catch (FreeTierLimitExceededException $e) {
            // Free user hit the active-QR limit (Pflichtenheft §2.4). Surface a
            // 402 Payment Required with a structured upgrade hint so the
            // Creator/Dashboard can render the Upgrade-Prompt deterministically.
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'free_tier_limit_exceeded',
                'upgrade_required' => true,
                'active_count' => $e->activeCount,
                'limit' => $e->limit,
                'upgrade_hint' => 'Upgrade to Pro or Business to create more active QR codes.',
            ], 402);
        } catch (SlugCollisionException $e) {
            return response()->json([
                'message' => 'Alias already taken.',
                'errors' => ['alias' => ['This alias is already in use.']],
            ], 422);
        }
    }

    /**
     * Free-Tier status for the Creator/Dashboard Upgrade-Prompt and active-count
     * display (Pflichtenheft §2.4): resolved tier, active count, limit,
     * remaining headroom and whether the limit has been reached.
     */
    public function freeTierStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QrCode::class);

        return response()->json(
            $this->service->freeTierStatus($request->user()),
        );
    }

    /**
     * Feature-gate flags for the Creator/Edit UI (P2-T07): which capabilities
     * (custom alias, password protection, branding, download profile) the
     * user's *current* plan unlocks, plus a deterministic upgrade hint for
     * Free users.
     */
    public function featureStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QrCode::class);

        return response()->json(
            $this->service->featureStatus($request->user()),
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $qrCode = $this->service->find($id);
        $this->authorize('view', $qrCode);

        return (new QrCodeResource($qrCode))->response();
    }

    public function update(UpdateQrCodeRequest $request, int $id): JsonResponse
    {
        $qrCode = $this->service->find($id);
        $this->authorize('update', $qrCode);

        try {
            $qrCode = $this->service->update($qrCode, $request->validated());

            return (new QrCodeResource($qrCode))->response();
        } catch (FeatureNotEntitledException $e) {
            // Editing a Free-snapshot code: custom alias / password protection
            // are Pro/Business-only (§2.1). A grandfathered paid code keeps its
            // rights (§3.5.3), so this only rejects genuinely Free codes.
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'feature_not_entitled',
                'upgrade_required' => true,
                'feature' => $e->feature,
                'plan' => $e->plan,
                'upgrade_hint' => $e->getMessage(),
            ], 402);
        } catch (SlugCollisionException $e) {
            return response()->json([
                'message' => 'Alias already taken.',
                'errors' => ['alias' => ['This alias is already in use.']],
            ], 422);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $qrCode = $this->service->find($id);
        $this->authorize('delete', $qrCode);
        $this->service->delete($qrCode);

        return response()->json(null, 204);
    }
}
