<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class ConsultationMedicale extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'consultation_medicale';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'fkidPatient',
        'fkidMedecin',
        'fkidCabinet',
        'fkidFacture',
        'date_consultation',
        'motif',
        'temperature',
        'tension_arterielle',
        'frequence_cardiaque',
        'spo2',
        'gad',
        'poids',
        'taille',
        'examen_clinique',
        'diagnostic',
        'conduite_a_tenir',
        'medicaments_prescrits',
        'examens_demandes',
        'ordonnances_ids',
        'notes',
    ];

    protected $casts = [
        'date_consultation'    => 'datetime',
        'medicaments_prescrits' => 'array',
        'examens_demandes'      => 'array',
        'ordonnances_ids'       => 'array',
        'fkidPatient'          => 'integer',
        'fkidMedecin'          => 'integer',
        'fkidCabinet'          => 'integer',
        'fkidFacture'          => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }

    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidMedecin', 'idMedecin');
    }
}
