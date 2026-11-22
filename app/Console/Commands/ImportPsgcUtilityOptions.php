<?php

namespace App\Console\Commands;

use App\Models\UtilityOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportPsgcUtilityOptions extends Command
{
    protected $signature = 'utilities:import-psgc {path : Full path to the PSGC xlsx datafile}';

    protected $description = 'Import PSGC regions, provinces, cities/municipalities, and barangays into utility options.';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("PSGC file not found: {$path}");

            return self::FAILURE;
        }

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            $this->error('Database connection is not available. Please start MySQL before importing PSGC options.');

            return self::FAILURE;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['PSGC']);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row === 1 || in_array($columnAddress, ['A', 'B', 'D'], true);
            }
        });

        $worksheet = $reader->load($path)->getSheetByName('PSGC');

        if (! $worksheet) {
            $this->error('The workbook does not contain a PSGC sheet.');

            return self::FAILURE;
        }

        $rows = $worksheet->toArray(null, true, true, true);
        $regions = [];
        $provinces = [];
        $localities = [];
        $options = [
            'ph_regions' => [],
            'ph_provinces' => [],
            'ph_cities' => [],
            'ph_barangays' => [],
        ];

        foreach (array_slice($rows, 1) as $row) {
            $code = trim((string) ($row['A'] ?? ''));
            $name = trim((string) ($row['B'] ?? ''));
            $level = trim((string) ($row['D'] ?? ''));

            if ($code === '' || $name === '' || $level === '') {
                continue;
            }

            if ($level === 'Reg') {
                $regionKey = substr($code, 0, 2);
                $regions[$regionKey] = $name;
                $options['ph_regions'][] = $this->optionRow('ph_regions', $name, null, null);

                continue;
            }

            $regionName = $regions[substr($code, 0, 2)] ?? null;

            if ($level === 'Prov') {
                $provinceKey = substr($code, 0, 5);
                $provinces[$provinceKey] = $name;
                $options['ph_provinces'][] = $this->optionRow('ph_provinces', $name, 'ph_regions', $regionName);

                continue;
            }

            if (in_array($level, ['City', 'Mun', 'SubMun'], true)) {
                $provinceKey = substr($code, 0, 5);
                $localityKey = substr($code, 0, 7);
                $provinceName = $provinces[$provinceKey] ?? null;
                $parentValue = $provinceName ?: $this->independentCityParent($regionName);

                if (! $provinceName && $regionName) {
                    $options['ph_provinces'][] = $this->optionRow('ph_provinces', $parentValue, 'ph_regions', $regionName);
                }

                $localities[$localityKey] = $name;
                $options['ph_cities'][] = $this->optionRow('ph_cities', $name, 'ph_provinces', $parentValue);

                continue;
            }

            if ($level === 'Bgy') {
                $localityName = $localities[substr($code, 0, 7)] ?? null;

                if ($localityName) {
                    $options['ph_barangays'][] = $this->optionRow('ph_barangays', $name, 'ph_cities', $localityName);
                }
            }
        }

        foreach ($options as $group => $rows) {
            UtilityOption::query()->where('group_key', $group)->delete();

            $sortOrder = 1;

            collect($rows)
                ->unique(fn (array $row) => $row['group_key'].'|'.$row['value'].'|'.($row['parent_value'] ?? ''))
                ->values()
                ->chunk(1000)
                ->each(function ($chunk) use (&$sortOrder) {
                    $now = now();
                    $payload = $chunk->map(function (array $row) use (&$sortOrder, $now) {
                        return array_merge($row, [
                            'sort_order' => $sortOrder++,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    })->all();

                    UtilityOption::insert($payload);
                });
        }

        $this->info('PSGC utility options imported successfully.');

        return self::SUCCESS;
    }

    private function optionRow(string $groupKey, string $value, ?string $parentGroup, ?string $parentValue): array
    {
        return [
            'group_key' => $groupKey,
            'label' => $value,
            'value' => $value,
            'parent_group' => $parentGroup,
            'parent_value' => $parentValue,
        ];
    }

    private function independentCityParent(?string $regionName): string
    {
        return 'Independent City'.($regionName ? ' - '.$regionName : '');
    }
}
