<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

/**
 * Class TFournisseurPersonnel
 *
 * @property int $IDFournisseur
 * @property string|null $NomTiers
 * @property string|null $TelephoneAutre
 * @property float|null $fkidtypeTiers
 * @property float|null $userCr
 * @property int $fkidcaibnet
 *
 * @package App\Models
 */
class TFournisseurPersonnel extends Model
{
	use BelongsToTenant;

	// Nom de colonne réel en base (faute de frappe historique : "fkidcaibnet"),
	// conservé tel quel pour ne pas nécessiter de migration de renommage.
	protected static $tenantColumn = 'fkidcaibnet';

	protected $table = 't_fournisseur_personnel';
	protected $primaryKey = 'IDFournisseur';
	public $timestamps = false;

	protected $casts = [
		'fkidtypeTiers' => 'float',
		'userCr' => 'float',
		'fkidcaibnet' => 'int'
	];

	protected $fillable = [
		'NomTiers',
		'TelephoneAutre',
		'fkidtypeTiers',
		'userCr',
		'fkidcaibnet'
	];
}
