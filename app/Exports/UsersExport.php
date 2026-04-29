<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected Collection $users;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    public function collection(): Collection
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Apellido',
            'Email',
            'Teléfono',
            'Plan',
            'Estado',
            'Perfil',
            'Localidad',
            'Provincia',
            'País',
            'Evento',
            'Tipo suscripción',
            'Fecha alta plan siembra',
            'Mes gratuito utilizado',
            'Email verificado',
            'Fecha verificación email',
            'Última actividad',
            'Código referido',
            'Referido por (ID)',
            'Fecha de registro',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->last_name,
            $user->email,
            $user->phone,
            $user->plan?->plan ?? '',
            $user->status?->name ?? '',
            $user->profile?->name ?? '',
            $user->locality?->name ?? $user->locality_name ?? '',
            $user->locality?->province_id ? ($user->locality->province->name ?? $user->province_name ?? '') : ($user->province_name ?? ''),
            $user->country?->name ?? '',
            $user->event?->name ?? '',
            $user->subscription_type === 'monthly' ? 'Mensual' : ($user->subscription_type === 'yearly' ? 'Anual' : ''),
            $user->plan_start_date ? $user->plan_start_date->format('d/m/Y H:i') : '',
            $user->free_trial_used ? 'Sí' : 'No',
            $user->email_confirmation ? 'Sí' : 'No',
            $user->email_confirmation ? \Carbon\Carbon::parse($user->email_confirmation)->format('d/m/Y H:i') : '',
            $user->last_activity_at ? $user->last_activity_at->format('d/m/Y H:i') : '',
            $user->referral_code ?? '',
            $user->referred_by ?? '',
            $user->created_at ? $user->created_at->format('d/m/Y H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2E7D32']],
            ],
        ];
    }
}
