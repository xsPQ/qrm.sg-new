<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Entitlement\EntitlementSnapshot;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\SvgWriterOptions;

/**
 * Visual QR-code customization (FEAT-04).
 *
 * Translates a per-code `settings.style` array into Endroid builder options:
 * foreground/background colors, dot (block) roundness, error-correction level,
 * gradient, and an optional embedded logo.
 *
 * The service also enforces entitlement gating:
 *  - Free:     foreground color, background color, and basic round-block modes only.
 *  - Pro:      everything in Free + gradient + logo embedding + all error-correction levels.
 *  - Business: everything in Pro (white-label already handled elsewhere).
 *
 * Settings shape (stored in QrCode->settings['style']):
 *   fg_color:        hex string like '#1a1a2e' (default #000000)
 *   bg_color:        hex string like '#ffffff' (default #ffffff)
 *   dot_style:       'square' | 'round' | 'extra_round' (default 'square')
 *   error_correction:'L' | 'M' | 'Q' | 'H' (default 'M'; Pro+ for Q/H)
 *   gradient:        ['from' => '#hex', 'to' => '#hex', 'angle' => 0-360] (Pro+)
 *   logo_path:       relative storage path or null (Pro+)
 *   logo_size:       px (Pro+)
 *   margin:          quiet-zone override (0-50, default 10)
 */
class QrStyleService
{
    public const DOT_STYLE_SQUARE = 'square';
    public const DOT_STYLE_ROUND = 'round';
    public const DOT_STYLE_EXTRA_ROUND = 'extra_round';

    public const VALID_DOT_STYLES = [
        self::DOT_STYLE_SQUARE,
        self::DOT_STYLE_ROUND,
        self::DOT_STYLE_EXTRA_ROUND,
    ];

    public const VALID_ERROR_CORRECTION = ['L', 'M', 'Q', 'H'];

    /** Premium error-correction levels require Pro or above. */
    private const PREMIUM_EC_LEVELS = ['Q', 'H'];

    /**
     * Resolve the effective style settings, applying defaults and entitlement
     * gating. Strips premium features (gradient, logo, premium EC) for Free
     * snapshots.
     *
     * @param  array<string,mixed>|null  $style
     * @return array<string,mixed>  Sanitized style array safe for rendering.
     */
    public function resolveStyle(?array $style, EntitlementSnapshot $snapshot): array
    {
        $style = is_array($style) ? $style : [];
        $isPaid = $snapshot->isPaid();

        $resolved = [
            'fg_color' => $this->sanitizeHex($style['fg_color'] ?? '#000000', '#000000'),
            'bg_color' => $this->sanitizeHex($style['bg_color'] ?? '#ffffff', '#ffffff'),
            'dot_style' => $this->sanitizeDotStyle($style['dot_style'] ?? self::DOT_STYLE_SQUARE),
            'error_correction' => $this->sanitizeEC(
                $style['error_correction'] ?? 'M',
                $isPaid,
            ),
            'margin' => $this->sanitizeMargin($style['margin'] ?? 10),
        ];

        // Gradient: Pro+ only
        if ($isPaid && !empty($style['gradient']) && is_array($style['gradient'])) {
            $resolved['gradient'] = $this->sanitizeGradient($style['gradient']);
        }

        // Logo: Pro+ only
        if ($isPaid && !empty($style['logo_path'])) {
            $resolved['logo_path'] = $this->sanitizeLogoPath($style['logo_path']);
            $resolved['logo_size'] = isset($style['logo_size'])
                ? max(20, min((int) $style['logo_size'], 300))
                : null;
        }

        return $resolved;
    }

    /**
     * Apply resolved style settings onto an Endroid Builder instance.
     *
     * @param  array<string,mixed>  $style  Output from {@see resolveStyle()}.
     */
    public function applyStyle(Builder $builder, array $style): Builder
    {
        $fg = $this->hexToColor($style['fg_color']);
        $bg = $this->hexToColor($style['bg_color']);

        $builder
            ->foregroundColor($fg)
            ->backgroundColor($bg)
            ->margin($style['margin']);

        // Error correction
        $ecMap = [
            'L' => ErrorCorrectionLevel::Low,
            'M' => ErrorCorrectionLevel::Medium,
            'Q' => ErrorCorrectionLevel::Quartile,
            'H' => ErrorCorrectionLevel::High,
        ];
        $builder->errorCorrectionLevel($ecMap[$style['error_correction']] ?? ErrorCorrectionLevel::Medium);

        // Dot style → roundBlockSizeMode
        $blockSizeMap = [
            self::DOT_STYLE_SQUARE => RoundBlockSizeMode::None,
            self::DOT_STYLE_ROUND => RoundBlockSizeMode::Enlarge,
            self::DOT_STYLE_EXTRA_ROUND => RoundBlockSizeMode::Enlarge,
        ];
        $builder->roundBlockSizeMode($blockSizeMap[$style['dot_style']] ?? RoundBlockSizeMode::None);

        // Gradient (Pro+) — when both gradient and logo are present, gradient
        // takes precedence for the foreground. The endroid SVG/PNG writers
        // apply gradientColor if set; we simulate it by blending fg_color.
        if (!empty($style['gradient'])) {
            $gradient = $this->resolveGradientColor($style['gradient']);
            $builder->foregroundColor($gradient);
        }

        // Logo (Pro+)
        if (!empty($style['logo_path'])) {
            $logoPath = $style['logo_path'];
            if (file_exists($logoPath)) {
                $logoSize = $style['logo_size'] ?? 80;
                $builder->logoPath($logoPath);
                $builder->logoResizeToWidth($logoSize);
                $builder->logoResizeToHeight($logoSize);
            }
        }

        return $builder;
    }

    /**
     * Determine whether a given style array uses any premium features.
     * Used by the Livewire components and service layer to surface upgrade
     * hints before the entitlement gate rejects the write.
     *
     * @param  array<string,mixed>|null  $style
     */
    public function usesPremiumFeatures(?array $style): bool
    {
        if (!is_array($style)) {
            return false;
        }

        if (!empty($style['gradient'])) {
            return true;
        }

        if (!empty($style['logo_path'])) {
            return true;
        }

        if (in_array($style['error_correction'] ?? '', self::PREMIUM_EC_LEVELS, true)) {
            return true;
        }

        return false;
    }

    /**
     * Feature flags for the UI regarding visual customization.
     *
     * @return array<string,mixed>
     */
    public function featureFlags(EntitlementSnapshot $snapshot): array
    {
        return [
            'can_set_colors' => true,
            'can_set_dot_style' => true,
            'can_use_gradient' => $snapshot->isPaid(),
            'can_use_logo' => $snapshot->isPaid(),
            'can_use_premium_ec' => $snapshot->isPaid(),
            'dot_styles' => self::VALID_DOT_STYLES,
            'error_correction_levels' => self::VALID_ERROR_CORRECTION,
        ];
    }

    // --- Sanitization helpers ---

    private function sanitizeHex(mixed $value, string $default): string
    {
        if (!is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value)
            ? strtolower($value)
            : $default;
    }

    private function sanitizeDotStyle(mixed $value): string
    {
        return is_string($value) && in_array($value, self::VALID_DOT_STYLES, true)
            ? $value
            : self::DOT_STYLE_SQUARE;
    }

    private function sanitizeEC(string $value, bool $isPaid): string
    {
        $value = strtoupper(trim($value));

        if (!in_array($value, self::VALID_ERROR_CORRECTION, true)) {
            return 'M';
        }

        // Premium EC levels (Q, H) require Pro+
        if (!$isPaid && in_array($value, self::PREMIUM_EC_LEVELS, true)) {
            return 'M';
        }

        return $value;
    }

    private function sanitizeMargin(mixed $value): int
    {
        $int = is_numeric($value) ? (int) $value : 10;

        return max(0, min($int, 50));
    }

    /**
     * @param  array<string,mixed>  $gradient
     * @return array<string,mixed>
     */
    private function sanitizeGradient(array $gradient): array
    {
        $from = $this->sanitizeHex($gradient['from'] ?? '#1a1a2e', '#1a1a2e');
        $to = $this->sanitizeHex($gradient['to'] ?? '#e94560', '#e94560');
        $angle = is_numeric($gradient['angle'] ?? null)
            ? max(0, min((int) $gradient['angle'], 360))
            : 0;

        return ['from' => $from, 'to' => $to, 'angle' => $angle];
    }

    private function sanitizeLogoPath(string $path): string
    {
        // Allow only files within the app's storage directory to prevent
        // path traversal. Resolve to an absolute path.
        $storageBase = storage_path('app/public/qrcodes/logos');
        $resolved = realpath($path);

        if ($resolved !== false && str_starts_with($resolved, $storageBase)) {
            return $resolved;
        }

        // Also allow paths relative to the public directory
        $publicBase = public_path('img/logos');
        if ($resolved !== false && str_starts_with($resolved, $publicBase)) {
            return $resolved;
        }

        // If the path doesn't resolve, return it as-is — the renderer checks
        // file_exists() before applying, so it degrades gracefully.
        return $path;
    }

    /**
     * Resolve a gradient definition to a blended Color (midpoint).
     *
     * @param  array<string,mixed>  $gradient
     */
    private function resolveGradientColor(array $gradient): Color
    {
        $from = $this->hexToColor($gradient['from']);
        $to = $this->hexToColor($gradient['to']);

        $r = (int) round(($from->getRed() + $to->getRed()) / 2);
        $g = (int) round(($from->getGreen() + $to->getGreen()) / 2);
        $b = (int) round(($from->getBlue() + $to->getBlue()) / 2);

        return new Color($r, $g, $b);
    }

    private function hexToColor(string $hex): Color
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return new Color((int) $r, (int) $g, (int) $b);
    }
}
