<?php

namespace App\Livewire;

use App\Support\PlateMath;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.public')]
#[Title('One Rep Max')]
class OneRepMaxCalculator extends Component
{
    /**
     * @var list<int>
     */
    private const Percents = [20, 30, 40, 50, 60, 70, 80, 90, 100];

    /**
     * @var array<int, string>
     */
    public const Bars = [
        44 => "Men's Bar (44 lb)",
        33 => "Women's Bar (33 lb)",
        45 => 'Trap Bar (45 lb)',
        25 => 'EZ Curl (25 lb)',
        0 => 'No bar',
    ];

    /**
     * @var array<int, string>
     */
    public const CommonBars = [
        44 => "Men's (44 lb)",
        33 => "Women's (33 lb)",
        45 => 'Trap (45 lb)',
        25 => 'EZ Curl (25 lb)',
    ];

    /**
     * @var list<string>
     */
    private const PlateSizes = ['45', '35', '25', '15', '10', '5', '2.5'];

    public string $max = '';

    public string $customPercent = '';

    public string $load = '70';

    public string $barWeight = '44';

    public function mount(): void
    {
        $this->normalizeBar();
    }

    public function updatedBarWeight(): void
    {
        $this->normalizeBar();
    }

    public function updatedMax(): void
    {
        $this->max = trim($this->max);

        if ($this->max === '') {
            $this->resetErrorBag('max');

            return;
        }

        $this->validate([
            'max' => ['numeric', 'gt:0', 'max:2000'],
        ]);
    }

    public function updatedCustomPercent(): void
    {
        $this->customPercent = trim($this->customPercent);

        $this->validate([
            'customPercent' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ]);

        if ($this->customPercent === '' || ! is_numeric($this->customPercent)) {
            if ($this->load === 'custom') {
                $this->load = '70';
            }

            return;
        }

        $percent = (float) $this->customPercent;
        $this->load = $this->isStandardPercent($percent) ? (string) (int) $percent : 'custom';
    }

    public function selectBar(int $weight): void
    {
        $this->barWeight = (string) $weight;
        $this->normalizeBar();
    }

    public function selectPercent(string $key): void
    {
        $this->load = $key;
    }

    public function render()
    {
        $this->normalizeBar();
        $rows = $this->rows();

        return view('livewire.one-rep-max-calculator', [
            'bars' => self::Bars,
            'commonBars' => self::CommonBars,
            'rows' => $rows,
            'selected' => $this->selectedRow($rows),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, target: string, loaded: string, plateCounts: list<array{size: string, count: int}>}>
     */
    private function rows(): array
    {
        $max = $this->maxValue();

        if ($max <= 0) {
            return [];
        }

        $rows = [];

        foreach (self::Percents as $percent) {
            $rows[] = $this->loadRow($max, (float) $percent, (string) $percent);
        }

        if ($this->customPercent !== '' && is_numeric($this->customPercent)) {
            $percent = (float) $this->customPercent;

            if ($percent >= 1 && $percent <= 100 && ! $this->isStandardPercent($percent)) {
                $rows[] = $this->loadRow($max, $percent, 'custom');
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{key: string, label: string, target: string, loaded: string, plateCounts: list<array{size: string, count: int}>}>  $rows
     * @return array{key: string, label: string, target: string, loaded: string, plateCounts: list<array{size: string, count: int}>}|null
     */
    private function selectedRow(array $rows): ?array
    {
        foreach ($rows as $row) {
            if ($row['key'] === $this->load) {
                return $row;
            }
        }

        foreach ($rows as $row) {
            if ($row['key'] === '70') {
                return $row;
            }
        }

        return $rows[0] ?? null;
    }

    /**
     * @return array{key: string, label: string, target: string, loaded: string, plateCounts: list<array{size: string, count: int}>}
     */
    private function loadRow(float $max, float $percent, string $key): array
    {
        $target = PlateMath::target($max, $percent);
        $plates = PlateMath::platesPerSide($target, (int) $this->barWeight);

        return [
            'key' => $key,
            'label' => $this->displayWeight($percent).'%',
            'target' => $this->displayWeight($target),
            'loaded' => $this->displayWeight(PlateMath::loaded($target, (int) $this->barWeight)),
            'plateCounts' => $this->plateCounts($plates),
        ];
    }

    /**
     * @param  array<string, int>  $plates
     * @return list<array{size: string, count: int}>
     */
    private function plateCounts(array $plates): array
    {
        $counts = [];

        foreach (self::PlateSizes as $size) {
            $count = $plates[$size.' lb'] ?? 0;

            if ($count < 1) {
                continue;
            }

            $counts[] = [
                'size' => $size,
                'count' => $count,
            ];
        }

        return $counts;
    }

    private function maxValue(): float
    {
        if ($this->max === '' || ! is_numeric($this->max)) {
            return 0.0;
        }

        $max = (float) $this->max;

        return $max > 0 && $max <= 2000 ? $max : 0.0;
    }

    private function isStandardPercent(float $percent): bool
    {
        return fmod($percent, 1.0) === 0.0 && in_array((int) $percent, self::Percents, true);
    }

    private function normalizeBar(): void
    {
        if ($this->barWeight === '' || ! array_key_exists((int) $this->barWeight, self::Bars)) {
            $this->barWeight = '44';
        }
    }

    private function displayWeight(mixed $weight): string
    {
        if ($weight === null || $weight === '') {
            return '';
        }

        $number = (float) $weight;

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
