<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Infocabinet
 * 
 * @property int $idEntete
 * @property string|null $NomCabFr
 * @property string|null $NomCabAr
 * @property string|null $Specialite1Fr
 * @property string|null $Specialite2fr
 * @property string|null $Specialite3Fr
 * @property string|null $Specialite1Ar
 * @property string|null $Specialite2Ar
 * @property string|null $Specialite3Ar
 * @property string|null $AdresseL1AR
 * @property string|null $AdresseL2AR
 * @property string|null $AdresseFr1
 * @property string|null $AdresseFr2
 * @property string|null $ContactAR
 * @property string|null $AdresseMail
 * @property string|null $ContactFR
 * @property string|null $TelephonePublic
 * @property string|null $DRAr
 * @property string|null $DrFr
 *
 * @package App\Models
 */
class Infocabinet extends Model
{
	protected $table = 'infocabinet';
	protected $primaryKey = 'idEntete';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'idEntete' => 'int'
	];

	protected $fillable = [
		'idEntete',
		'statut',
		'NomCabFr',
		'NomCabAr',
		'Specialite1Fr',
		'Specialite2fr',
		'Specialite3Fr',
		'Specialite1Ar',
		'Specialite2Ar',
		'Specialite3Ar',
		'AdresseL1AR',
		'AdresseL2AR',
		'AdresseFr1',
		'AdresseFr2',
		'ContactAR',
		'AdresseMail',
		'ContactFR',
		'TelephonePublic',
		'DrAr',
		'DrFr',
		'logo',
		'piedPage',
	];

	public function users()
	{
		return $this->hasMany(TUser::class, 'fkidcabinet', 'idEntete');
	}

	public function subscription()
	{
		return $this->hasOne(CabinetSubscription::class, 'idEntete', 'idEntete');
	}

	/**
	 * Mapping champ logique => [colonne fr, colonne ar]. Les noms de colonnes
	 * ci-dessous reflètent des incohérences de casse déjà présentes en base
	 * (Specialite2fr en minuscule, DrAr vs DRAr en migration) — non renommées
	 * pour ne rien casser d'existant.
	 */
	protected const TRANSLATABLE_FIELDS = [
		'nom_cab' => ['NomCabFr', 'NomCabAr'],
		'dr' => ['DrFr', 'DrAr'],
		'specialite1' => ['Specialite1Fr', 'Specialite1Ar'],
		'specialite2' => ['Specialite2fr', 'Specialite2Ar'],
		'specialite3' => ['Specialite3Fr', 'Specialite3Ar'],
		'adresse1' => ['AdresseFr1', 'AdresseL1AR'],
		'adresse2' => ['AdresseFr2', 'AdresseL2AR'],
		'contact' => ['ContactFR', 'ContactAR'],
	];

	/**
	 * Retourne la valeur d'un champ bilingue selon la locale demandée
	 * (par défaut la locale applicative courante).
	 */
	public function trans(string $field, ?string $locale = null): ?string
	{
		if (!isset(self::TRANSLATABLE_FIELDS[$field])) {
			return null;
		}

		[$colFr, $colAr] = self::TRANSLATABLE_FIELDS[$field];
		$locale = $locale ?? app()->getLocale();

		return $locale === 'ar' ? $this->{$colAr} : $this->{$colFr};
	}
}
