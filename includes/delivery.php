<?php

declare(strict_types=1);

function deliveryConfig(): array
{
    return [
        'min_notice_days' => 1,
        'time_min'        => '09:00',
        'time_max'        => '19:00',
        'areas' => [
            [
                'nome'       => 'Pindamonhangaba',
                'cep_inicio' => '12400000',
                'cep_fim'    => '12449999',
                'taxa'       => 2.00,
            ],
        ],
        'out_of_area_message' => 'No momento atendemos apenas CEPs de Pindamonhangaba entre 12400-000 e 12449-999.',
    ];
}

function deliveryNormalizeCep(?string $cep): string
{
    return preg_replace('/\D/', '', $cep ?? '');
}

function deliveryFormatCep(?string $cep): string
{
    $digits = deliveryNormalizeCep($cep);
    if (strlen($digits) !== 8) {
        return '';
    }

    return substr($digits, 0, 5) . '-' . substr($digits, 5);
}

function deliveryFindArea(?string $cep): ?array
{
    $digits = deliveryNormalizeCep($cep);
    if (strlen($digits) !== 8) {
        return null;
    }

    foreach (deliveryConfig()['areas'] as $area) {
        if ($digits >= $area['cep_inicio'] && $digits <= $area['cep_fim']) {
            return $area;
        }
    }

    return null;
}

function deliveryMinimumDate(): string
{
    $days = max(1, (int) (deliveryConfig()['min_notice_days'] ?? 1));
    return date('Y-m-d', strtotime('+' . $days . ' day'));
}

function deliveryTimeIsValid(?string $time): bool
{
    if (!is_string($time) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
        return false;
    }

    $config = deliveryConfig();
    return $time >= $config['time_min'] && $time <= $config['time_max'];
}
