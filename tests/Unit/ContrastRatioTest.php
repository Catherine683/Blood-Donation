<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ContrastRatioTest extends TestCase
{
    /**
     * Compute relative luminance of an sRGB hex color.
     */
    protected function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;

        $transform = function (float $c): float {
            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $transform($r) + 0.7152 * $transform($g) + 0.0722 * $transform($b);
    }

    /**
     * Compute WCAG contrast ratio between two hex colors.
     */
    protected function contrastRatio(string $fg, string $bg): float
    {
        $l1 = $this->relativeLuminance($fg);
        $l2 = $this->relativeLuminance($bg);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public function test_all_design_system_text_token_pairs_meet_wcag_aa_4_point_5_to_1(): void
    {
        $textPairs = [
            'neutral_900_on_neutral_50' => ['#1F1A1A', '#FBF8F6'],
            'neutral_800_on_neutral_50' => ['#342E29', '#FBF8F6'],
            'neutral_700_on_neutral_50' => ['#4D443D', '#FBF8F6'],
            'neutral_900_on_white' => ['#1F1A1A', '#FFFFFF'],
            'neutral_800_on_white' => ['#342E29', '#FFFFFF'],
            'neutral_700_on_white' => ['#4D443D', '#FFFFFF'],
            'white_on_primary_600' => ['#FFFFFF', '#B3262E'],
            'primary_700_on_neutral_50' => ['#961D23', '#FBF8F6'],
            'primary_800_on_primary_100' => ['#7C1B20', '#FDE5E6'],
            'success_800_on_success_100' => ['#14532D', '#DCFCE7'],
            'warning_800_on_warning_100' => ['#78350F', '#FEF3C7'],
            'danger_800_on_danger_100' => ['#991B1B', '#FEE2E2'],
            'info_800_on_info_100' => ['#1E3A8A', '#DBEAFE'],
            'white_on_success_600' => ['#FFFFFF', '#15803D'],
            'white_on_danger_600' => ['#FFFFFF', '#DC2626'],
        ];

        foreach ($textPairs as $name => [$fg, $bg]) {
            $ratio = $this->contrastRatio($fg, $bg);
            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "Token pair '{$name}' ({$fg} on {$bg}) has contrast ratio {$ratio}:1, failing WCAG AA (min 4.5:1)"
            );
        }
    }

    public function test_all_design_system_large_text_and_ui_borders_meet_wcag_3_to_1(): void
    {
        $uiPairs = [
            'neutral_600_on_neutral_50' => ['#62574E', '#FBF8F6'],
            'neutral_600_on_white' => ['#62574E', '#FFFFFF'],
            'primary_600_on_white' => ['#B3262E', '#FFFFFF'],
            'primary_600_on_neutral_50' => ['#B3262E', '#FBF8F6'],
            'warning_700_on_white' => ['#92400E', '#FFFFFF'],
            'danger_700_on_white' => ['#B91C1C', '#FFFFFF'],
        ];

        foreach ($uiPairs as $name => [$fg, $bg]) {
            $ratio = $this->contrastRatio($fg, $bg);
            $this->assertGreaterThanOrEqual(
                3.0,
                $ratio,
                "UI token pair '{$name}' ({$fg} on {$bg}) has contrast ratio {$ratio}:1, failing WCAG UI border (min 3:1)"
            );
        }
    }
}
