<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisingInteraction extends Model
{
    use HasFactory;

    protected $table = 'advertising_interactions';

    protected $fillable = [
        'id_company_advertising',
        'id_company_plan_publicity',
        'interaction_type',
        'user_id',
        'context_data',
    ];

    protected $casts = [
        'context_data' => 'array',
        'created_at' => 'datetime',
    ];

    // No actualizar timestamps automáticamente (solo created_at)
    const UPDATED_AT = null;

    /**
     * Relación con CompanyAdvertising
     */
    public function company_advertising()
    {
        return $this->belongsTo(CompanyAdvertising::class, 'id_company_advertising');
    }

    /**
     * Relación con CompanyPlanPublicity
     */
    public function company_plan_publicity()
    {
        return $this->belongsTo(CompanyPlanPublicity::class, 'id_company_plan_publicity');
    }

    /**
     * Relación con User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope para filtrar por tipo de interacción
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope para filtrar impresiones
     */
    public function scopeImpressions($query)
    {
        return $query->where('interaction_type', 'impression');
    }

    /**
     * Scope para filtrar clicks
     */
    public function scopeClicks($query)
    {
        return $query->where('interaction_type', 'click');
    }

    /**
     * Scope para filtrar por publicidad de empresa
     */
    public function scopeForCompanyAdvertising($query, $id)
    {
        return $query->where('id_company_advertising', $id);
    }

    /**
     * Scope para filtrar por publicidad de plan
     */
    public function scopeForCompanyPlanPublicity($query, $id)
    {
        return $query->where('id_company_plan_publicity', $id);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Registrar una nueva interacción
     */
    public static function recordInteraction($type, $advertisingId = null, $planPublicityId = null, $userId = null, $contextData = [])
    {
        return self::create([
            'id_company_advertising' => $advertisingId,
            'id_company_plan_publicity' => $planPublicityId,
            'interaction_type' => $type,
            'user_id' => $userId,
            'context_data' => $contextData,
        ]);
    }
}
