<?php

declare(strict_types=1);

namespace Logbook\Support\View;

use JsonSerializable;
use Logbook\Support\Display\DisplayPreferences;

/**
 * Data for a (stacked) bar chart over labelled categories, rendered by
 * assets/js/app.js with Chart.js. Like LineChart it is progressive
 * enhancement: every bar chart sits next to a table with the same figures.
 * Labels arrive already formatted and values already in display units.
 */
final class BarChart implements JsonSerializable
{
    /** @var list<array{label: string, color: string, type: string, values: list<?float>}> */
    private array $series = [];
    /** @var list<bool> bars drawn hatched (a partial period), one per label */
    private array $hatched = [];
    /** @var list<string> where a click on each bar goes, one per label; none when empty */
    private array $links = [];

    /**
     * @param list<string> $labels one per bar, e.g. localised month names
     */
    public function __construct(
        private readonly DisplayPreferences $preferences,
        private readonly array $labels,
        private readonly int $decimals,
        /** Currency code when values are money. */
        private readonly ?string $currency = null,
        private readonly bool $stacked = true,
        /** Axis title when values are not money, e.g. "mpg (UK)". */
        private readonly ?string $unit = null,
    ) {
    }

    /**
     * @param list<?float> $values one per label; null leaves a gap
     * @param string $color a colour token from app.css, e.g. "c-fuel"
     * @param bool $line drawn as a line over the bars (never stacked)
     */
    public function addSeries(string $label, array $values, string $color, bool $line = false): self
    {
        $this->series[] = [
            'label' => $label,
            'color' => $color,
            'type' => $line ? 'line' : 'bar',
            'values' => array_map(static fn (?float $v): ?float => $v === null ? null : round($v, 6), $values),
        ];

        return $this;
    }

    /**
     * Hatch the bars of partial periods (spec.md §7.35: a year so far).
     *
     * @param list<bool> $hatched one per label
     */
    public function hatch(array $hatched): self
    {
        $this->hatched = $hatched;

        return $this;
    }

    /**
     * Make each bar a link (spec.md §7.8 *Monthly spend*). The page keeps
     * the same links in its table, for the keyboard and without JS.
     *
     * @param list<string> $links one URL per label
     */
    public function links(array $links): self
    {
        $this->links = $links;

        return $this;
    }

    /**
     * Worth drawing: some bar above zero.
     */
    public function hasData(): bool
    {
        foreach ($this->series as $series) {
            foreach ($series['values'] as $value) {
                if ($value > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The bars' labels, in drawing order (oldest first for a time axis), so
     * a template can lay the figures out as a table (spec.md §8 *Printing
     * reports*, K192, #618).
     *
     * @return list<string>
     */
    public function labels(): array
    {
        return $this->labels;
    }

    /**
     * Each series' name and values (one per label, null for a gap), in the
     * order added: the columns of the chart's table.
     *
     * @return list<array{label: string, values: list<?float>}>
     */
    public function series(): array
    {
        return array_map(
            static fn (array $series): array => ['label' => $series['label'], 'values' => $series['values']],
            $this->series,
        );
    }

    /** Decimal places the chart shows its values at. */
    public function decimals(): int
    {
        return $this->decimals;
    }

    /** Currency code when the values are money; null otherwise. */
    public function currency(): ?string
    {
        return $this->currency;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => 'bar',
            'locale' => str_replace('_', '-', $this->preferences->locale),
            'labels' => $this->labels,
            'decimals' => $this->decimals,
            'currency' => $this->currency,
            'stacked' => $this->stacked,
            'unit' => $this->unit,
            'series' => $this->series,
            'hatched' => $this->hatched,
            'links' => $this->links,
        ];
    }
}
