<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Rendezvou;
use App\Models\Facture;
use App\Models\CaisseOperation;
use App\Models\Concerns\BelongsToTenant;

/**
 * Class Medecin
 * 
 * @property int $idMedecin
 * @property string|null $Nom
 * @property string $Contact
 * @property Carbon|null $DtAjout
 * @property int $fkidcabinet
 *
 * @package App\Models
 */
class Medecin extends Model
{
	use BelongsToTenant;

	protected static $tenantColumn = 'fkidcabinet';

	protected $table = 'medecins';
	protected $primaryKey = 'idMedecin';
	public $timestamps = false;


	protected $casts = [
		'DtAjout' => 'datetime',
		'fkidcabinet' => 'int'
	];

	protected $fillable = [
		'Nom',
		'Contact',
		'DtAjout',
		'fkidcabinet'
	];

	/**
	 * Relation avec le cabinet
	 */
	public function cabinet()
	{
		return $this->belongsTo(Cabinet::class, 'fkidcabinet', 'idCabinet');
	}

	public function rendezvous()
	{
		return $this->hasMany(Rendezvou::class, 'fkidMedecin', 'idMedecin');
	}

	public function factures()
	{
		return $this->hasMany(Facture::class, 'FkidMedecinInitiateur', 'idMedecin');
	}

	public function caisseOperations()
	{
		return $this->hasMany(CaisseOperation::class, 'fkidmedecin', 'idMedecin');
	}
}
