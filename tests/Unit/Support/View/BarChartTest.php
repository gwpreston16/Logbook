<?php

declare(strict_types=1);

namespace Logbook\Tests\Unit\Support\View;

use Logbook\Kernel;
use Logbook\Support\Display\DisplayPreferences;
use Logbook\Support\View\BarChart;
use PHPUnit\Framework\TestCase;

/**
 * A bar chart's figures read back for its table (spec.md §8 *Printing
 * reports*, K192, #618), and the CSS that keeps a hint about the drawn
 * chart off the page without JS (#617).
 */
final class BarChartTest extends TestCase
{
    public function testItReadsBackItsLabelsSeriesAndPrecision(): void
    {
        $chart = (new BarChart(DisplayPreferences::defaults('en_GB', 'Europe/London', 'GBP'), ['2025', '2026'], 3, 'GBP', false))
            ->addSeries('Golf', [0.3123456789, 0.25], 'accent', true)
            ->addSeries('Polo', [null, 0.2], 'c-maint', true);

        self::assertSame(['2025', '2026'], $chart->labels());
        self::assertSame([
            ['label' => 'Golf', 'values' => [0.312346, 0.25]],
            ['label' => 'Polo', 'values' => [null, 0.2]],
        ], $chart->series(), 'names and values only, rounded as drawn; a gap stays null');
        self::assertSame(3, $chart->decimals());
        self::assertSame('GBP', $chart->currency());
        self::assertNull((new BarChart(DisplayPreferences::defaults('en_GB', 'Europe/London', 'GBP'), [], 0))->currency());
    }

    public function testTheChartHintShowsOnlyWithJs(): void
    {
        $css = (string) file_get_contents(Kernel::rootDir() . '/assets/css/app.css');
        self::assertMatchesRegularExpression('/^\.chart-hint\s*\{\s*display:\s*none;\s*\}/m', $css);
        self::assertMatchesRegularExpression('/^\.js \.chart-hint\s*\{\s*display:\s*block;\s*\}/m', $css);
    }
}
