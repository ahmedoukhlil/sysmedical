# Roadmap — De l'application mono-tenant au SaaS multi-tenant

**Référence** : découle de [AUDIT.md](AUDIT.md) (2026-08-23).
**Principe directeur** : aucune vente à un second cabinet avant la fin de la Phase 0.

Chaque chantier indique : objectif, pourquoi (issu de l'audit), fichiers/zones concernés, et une estimation d'effort relative (S = quelques jours, M = 1-2 semaines, L = 2-4 semaines) — à ajuster selon les ressources réelles.

---

## Phase 0 — Sécurisation (bloquant, avant tout démarchage multi-clients)

Objectif : rendre l'application sûre à héberger pour plusieurs clients, sans encore construire les fonctionnalités SaaS.

### 0.1 Isolation tenant systématique — **L**
- Créer un trait `BelongsToTenant` + Global Scope Eloquent appliqué à tous les modèles portant `fkidCabinet`.
- Ajouter un middleware qui résout le "cabinet courant" depuis l'utilisateur connecté et l'injecte dans le contexte applicatif (au lieu des `where('fkidcabinet', ...)` manuels dispersés).
- Auditer et remplacer les requêtes inline actuelles (`routes/web.php:38-85`, composants Livewire god-object) par ce mécanisme.
- Cas particulier : réconcilier les modèles `User`/`TUser` en doublon avant d'y accrocher le trait, pour ne pas dupliquer la logique de scoping.

### 0.2 Retirer les données patients des logs — **S**
- Auditer tous les `Log::info`/`Log::debug` contenant des objets patient/dossier médical complets.
- Remplacer par des logs ne portant que des identifiants techniques (ID, pas de nom/diagnostic/allergie).

### 0.3 Upgrade Laravel 8 → 10 ou 11 — **L**
- Mettre à jour Laravel, Sanctum, et migrer Laravel Mix → Vite (cohérence avec Tailwind v4 déjà en place).
- Traiter en parallèle car nombre de breaking changes Livewire 2→3 possible selon la cible choisie (à trancher : rester Livewire 2 sur Laravel 10, ou upgrader aussi vers Livewire 3).
- Revalider manuellement les workflows critiques (facturation, règlement, ordonnances) après upgrade — aucun test automatisé n'existe pour sécuriser ce chantier (cf. 0.4).

### 0.4 Tests automatisés sur le workflow critique — **M**
- Prioriser : création facture, règlement, déduction de stock via ordonnance urgence, isolation tenant (test qu'un cabinet A ne voit jamais les données du cabinet B).
- Objectif réaliste : pas 100% de couverture, mais un filet de sécurité sur les chemins qui manipulent argent/stock/données patients.

### 0.5 Index base de données manquants — **S**
- Ajouter les index sur `fkidCabinet` pour `facture`, `caisse_operations`, `ordonnanceref`, `boncommande`, `bordereauxfactures`.

**Sortie de phase** : l'application peut héberger plusieurs cabinets dans la même base sans risque de fuite croisée, sur un stack maintenu.

---

## Phase 1 — Fondations SaaS

Objectif : transformer l'outil sécurisé en produit vendable en autonomie.

### 1.1 Couche admin plateforme (super-admin) — **M**
- Nouvel espace `/admin` avec guard dédié.
- Fonctions : lister les cabinets, créer/suspendre un cabinet, voir l'usage global (nombre d'utilisateurs, stockage, dernière activité).

### 1.2 Onboarding self-service — **Reporté, hors périmètre**
- Décision produit (2026-08-24) : pas d'auto-inscription publique — la création de cabinet reste réservée à l'admin plateforme (fait en 1.1, `Admin\CabinetController::store()`).
- L'assistant d'import de patients existants (CSV) reste pertinent mais recadré : à faire depuis l'espace applicatif du cabinet (pas l'admin), en chantier séparé si besoin plus tard.

### 1.3 Facturation d'abonnement — **M**
- Intégration paiement (Stripe et/ou solution mobile money locale selon marché cible).
- Gestion essai gratuit (14-30 jours), suspension automatique en cas d'impayé, paliers de plan (Essentiel/Standard/Pro définis dans l'audit business §3.3).

### 1.4 Quotas et sauvegardes par tenant — **S**
- Limites de stockage fichiers par cabinet (dossiers médicaux/scans).
- Politique de sauvegarde/export des données par tenant (traite aussi une partie de la conformité, cf. 1.5).

### 1.5 Conservation des données conforme — **S**
- Remplacer la suppression cascade irréversible (patients/factures) par un mécanisme d'archivage/anonymisation respectant une durée de conservation définie.

**Sortie de phase** : un nouveau cabinet peut s'inscrire, payer et démarrer seul, sans intervention manuelle.

---

## Phase 2 — Qualité produit

Objectif : réduire la dette qui freinera les évolutions futures et l'expérience utilisateur au quotidien.

### 2.1 Design system minimal — **M** ✅ Fait (2026-08-27)
- 4 composants Blade créés (`resources/views/components/`) : `status-badge`, `button`, `card`, `form-input` — enveloppent le CSS déjà présent dans `resources/css/app.css` (`.btn-*`, `.card*`, `.form-input`), sans nouvelle classe CSS.
- 2 fichiers migrés en preuve de concept : `create-rendez-vous.blade.php` (switch statut RDV dupliqué) et `admin/cabinets/create.blade.php` (bouton/carte/champs). `rendez-vous-manager.blade.php` volontairement laissé tel quel : ses libellés de statut ("Présent au cabinet", "Avec le médecin") diffèrent de ceux déjà codés dans `x-status-badge`, migrer aurait changé le texte affiché — décision de ne pas casser la fidélité visuelle plutôt que de forcer la migration.
- **Convention pour la suite** : tout nouveau code utilise `<x-status-badge>`, `<x-button>`, `<x-card>`, `<x-form-input>` au lieu de classes Tailwind brutes ou d'un switch PHP recopié. Migration des vues existantes uniquement en marge d'un autre ticket qui touche déjà le fichier — pas de PR dédiée "migration design system" en masse. `x-status-badge` en `domain="generic"` + prop `:map` sert pour tout nouveau statut métier (abonnement, utilisateur, paiement) tant qu'il n'y a pas 3+ fichiers qui répètent le même mapping — au-delà, envisager un vrai domaine dédié.

### 2.2 Feedback utilisateur — **S** ✅ Fait (2026-08-27)
- Le système toast construit en Phase 2.1 (`window.showToast`, pont `Livewire.on('toast')`, CSS `.toast`/`.spinner`) était quasi inexploité (5 `emit('toast')` contre 178 `session()->flash()`) — ajouté aussi à `layouts/admin.blade.php`, qui ne l'avait pas.
- `wire:loading` généralisé sur les ~21 actions d'ouverture de modal de `AccueilPatient` (composant hub, 48 `wire:click`, précédemment 0 `wire:loading`) : icône masquée + spinner `.spinner-dark` pendant le chargement, bouton désactivé via `wire:loading.attr="disabled"`.
- 4 composants migrés de `session()->flash()` vers `$this->emit('toast', ['message' => '...', 'type' => 'success'|'error'])` : `AccueilPatient`, `ActeCreate`, `AssureurCreate`, `ParametresCabinet`. Blocs HTML de flash dupliqués retirés des vues correspondantes.
- Nettoyage : 2 messages de debug oubliés dans `HistoriquePaiement.php` ("Méthode imprimer appelée") supprimés — jamais destinés à l'utilisateur.
- **Convention pour la suite** : tout nouveau feedback utilisateur Livewire utilise `$this->emit('toast', [...])`, pas `session()->flash()`. Migration des 19 fichiers restants au fil de l'eau, pas de big-bang. `wire:loading` : `wire:loading.attr="disabled"` + `wire:target="methode"` sur le déclencheur, spinner via classe `.spinner`/`.spinner-dark` (pas `fa-spinner fa-spin` pour le nouveau code). Les 7 mini-modals de confirmation maison restent en l'état ; `window.confirmAction` (Phase 2.1) réservé aux nouveaux cas.

### 2.3 Accessibilité de base — **S** ✅ Fait (2026-08-27)
- File d'attente du portail patient (`patient/rendez-vous.blade.php`) convertie en composant Livewire `PatientQueueStatus` avec `wire:poll.30s`, remplaçant le `setTimeout(() => location.reload(), 30000)`. `aria-live="polite" aria-atomic="true"` sur le conteneur du tableau — les mises à jour sont désormais annoncées aux lecteurs d'écran sans rechargement complet de page.
- Les 23 modals de `AccueilPatient` ont maintenant `role="dialog"`, `aria-modal="true"`, `aria-labelledby` (pointant vers le titre), `tabindex="-1"` sur `.modal-box`, `aria-label="Fermer"` sur le bouton de fermeture. Script JS mutualisé ajouté (focus trap Tab/Shift+Tab, capture du focus à l'ouverture, restauration au déclencheur à la fermeture) — remplace le handler Escape isolé qui existait auparavant.
- **Dette documentée** : `x-status-badge` (Phase 2.1) n'a toujours qu'1 seul consommateur réel (`create-rendez-vous.blade.php`) — non traité ici, candidat pour une future passe de migration du design system (`rendez-vous-manager.blade.php`, badges du portail patient en arabe, `admin/cabinets/*`, etc.).
- **Dette documentée** : la logique de décodage de token (`decodeTokenParts`/`getDateFromToken`/`getMedecinIdFromToken`/`decodeToken`) est dupliquée entre `PatientInterfaceController` et `PatientQueueStatus` — à extraire en Trait/Service si un 3e consommateur apparaît.
- **Convention** : les ~22 modals restants hors `AccueilPatient` (pharmacie, médicaments, etc.) suivent le même pattern `.modal-overlay`/`.modal-box` et bénéficient déjà du script JS mutualisé (le `MutationObserver` observe tout le document) — l'ajout ARIA (`role`/`aria-modal`/`tabindex`) reste à faire au fil de l'eau sur ces fichiers.

### 2.4 i18n structurée — **M** ✅ Fait (2026-08-25)
- Mis en place le système de traduction Laravel natif : `config/app.php` corrigé (`locale`/`fallback_locale` de `'en'` vers `'fr'`, jamais adapté auparavant), `resources/lang/fr/{patient,rdv}.php` et `resources/lang/ar/{patient,rdv}.php` créés. Middleware `SetPatientLocale` (`app/Http/Middleware/SetPatientLocale.php`, alias `patient.locale`) force la locale `ar` uniquement sur la route `patient.rendez-vous` — le reste de l'app (back-office, guard `admin`) reste en `fr`.
- **Périmètre recentré après exploration détaillée** : sur les 8 vues initialement identifiées avec du texte arabe en dur, seules 2 sont de la vraie UI à locale fixe et ont été migrées vers `__()` : `resources/views/patient/rendez-vous.blade.php` et `resources/views/livewire/patient-queue-status.blade.php`. Les 6 autres (`rendez-vous/print`, `consultations/{ordonnance,receipt}`, `ordonnances/print`, `livewire/{rdv-reminders,parametres-cabinet}`) affichent du contenu bilingue fr+ar intentionnellement côte à côte (documents officiels imprimés, formulaire de saisie bilingue, message WhatsApp bilingue) — un choix produit délibéré, pas de la dette à corriger. Les migrer vers `__()` aurait ajouté de l'indirection sans bénéfice, le texte restant de toute façon bilingue simultané à l'écran.
- `app/Support/RdvStatus.php` (nouveau) : factorise le mapping statut RDV → clé canonique (`en_attente|confirme|en_cours|termine|annule|consultation`), reproduisant à l'identique la logique du `@switch` PHP précédemment dupliqué dans `patient-queue-status.blade.php` (Phase 2.3). Les libellés eux-mêmes viennent maintenant de `rdv.statuts.*` dans les fichiers de langue.
- `Infocabinet::trans(string $field, ?string $locale = null)` (nouveau, `app/Models/Infocabinet.php`) : accessor unifié pour les 8 paires de colonnes Fr/Ar (`NomCabFr`/`NomCabAr`, `Specialite1-3Fr`/`Ar`, `AdresseFr1/2`/`AdresseL1AR/L2AR`, `ContactFR`/`ContactAR`, `DrFr`/`DrAr`), sans package tiers (solution maison, `spatie/laravel-translatable` jugé disproportionné pour 2 langues fixes sur un seul modèle). Colonnes existantes non renommées malgré leurs incohérences de casse déjà présentes (`Specialite2fr` minuscule, `DRAr` en migration vs `DrAr` dans le modèle) — documentées en commentaire plutôt que corrigées, pour ne rien casser d'existant.
- **Bug multi-tenant préexistant corrigé** (découvert en creusant les usages d'`Infocabinet`, indépendant de l'i18n) : plusieurs vues d'impression (`ordonnances/print.blade.php`, `consultations/facture-patient.blade.php`, `factures/facture.blade.php`, `layouts/app.blade.php`, `DossierMedicalController::printPatient`) et les partials `partials/{recu-header,recu-footer}.blade.php` appelaient `Infocabinet::first()` sans filtrer par tenant, risquant d'afficher l'en-tête du mauvais cabinet sur un document imprimé si plusieurs cabinets existent en base. Remplacé par une résolution via le `fkidCabinet`/`fkidcabinet` du document ou de l'utilisateur connecté, avec `Log::warning()` (pas d'exception) si aucun cabinet n'est résolvable.
- Dette documentée, hors périmètre de ce chantier (ampleur bien plus réduite qu'`Infocabinet`, tickets futurs proportionnés si besoin) : `Acte::ActeArab` (3 composants Livewire : `ActeCreate`, `ActeManager`, `ListeActes` + 3 vues, actif et affiché — le candidat le plus sérieux pour un futur chantier du même type qu'`Infocabinet::trans()`), `Detailfacturepatient::ActesArab` (écrit en dur avec la valeur littérale `'NR'` dans 4 composants, non exploité en lecture), `Factureaimprimer::ActesAr/TypeAr/NomAr` (aucun usage actif trouvé au-delà du modèle/migration, dette dormante).
- **Convention pour la suite** : tout nouveau texte d'UI destiné à un public à locale unique (portail patient, futures interfaces localisées) passe par `__()`/`@lang` avec des clés dans `resources/lang/{fr,ar}/*.php` — jamais de texte en dur. Le contenu intentionnellement bilingue simultané (documents officiels, reçus, messages WhatsApp) reste tel quel : ce n'est pas le même problème que le texte en dur non traduit.

**Sortie de phase** : produit plus robuste et cohérent visuellement, prêt à scaler en nombre de vues/fonctionnalités.

---

## Phase 3 — Différenciateurs commerciaux

Objectif : construire l'avantage compétitif une fois le socle sécurisé et vendable.

- **Paiement mobile local intégré** (Bankily, Masrvi, Orange Money) dans le règlement/caisse — **M** — Reporté (2026-08-26), chantier séparé à traiter plus tard.
- **Portail patient enrichi** : prise de RDV en ligne, rappels SMS/WhatsApp — **M** ✅ Fait (2026-08-26)
- **Module tiers-payant avancé** : bordereaux automatiques groupés par assureur — **M**
- **Application mobile légère praticiens** (agenda, salle d'attente/soins temps réel) — **L** ✅ Fait (2026-08-26)

Ces chantiers sont volontairement en dernier : ils apportent de la valeur commerciale mais n'ont pas de sens à construire sur une fondation multi-tenant non sécurisée.

### Portail patient enrichi — détail

- **Authentification OTP par WhatsApp** : un patient déjà connu du cabinet (identifié par `Telephone1`/`Telephone2`) reçoit un code à usage unique pour accéder à un calendrier de créneaux. Pas d'auto-inscription — un nouveau patient doit toujours passer par le cabinet. Nouveaux composants `app/Http/Livewire/PatientOtpLogin.php` et `PatientBookingCalendar.php`, service de tokens signés `app/Services/PatientTokenService.php` (distinct des tokens de suivi de RDV existants dans `PatientInterfaceController`, volontairement non fusionnés pour ne pas risquer de régression sur un mécanisme déjà en prod).
- **Service WhatsApp Business API « prêt à brancher »** (`app/Services/WhatsAppService.php`, `config/services.php`) : mode dry-run (log au lieu d'un envoi réel) tant que `WHATSAPP_TOKEN`/`WHATSAPP_PHONE_NUMBER_ID` ne sont pas renseignés en `.env`. **Credentials Meta Business réels à obtenir côté business avant toute mise en production** — le code est fonctionnel mais rien n'est envoyé réellement sans eux.
- **Anti-double-booking** : `Rendezvou::createWithLock()` (verrou pessimiste `lockForUpdate()`, même patron que `generateNextOrderNumber()` déjà existant) remplace l'ancien `hasConflict()` non protégé contre la concurrence — réutilisé par le flux staff (`CreateRendezVous.php`) et le nouveau flux patient. Pas de contrainte unique DB stricte en V1 (dette actée : MySQL gère mal les index uniques partiels excluant les RDV annulés).
- **Bug corrigé en cours de route** : `Rendezvou::hasConflict()` parsait `HeureRdv` (stockée en base comme heure seule `H:i`) sans la recombiner avec la date du RDV, donc `Carbon::parse()` résolvait implicitement à la date du jour — un conflit sur une date future n'était jamais détecté. Corrigé en recombinant explicitement avec `$date` avant parsing (`app/Models/Rendezvou.php`).
- **Horaires et durée de RDV configurables par cabinet** (pas par médecin, dette assumée) : nouvelles colonnes `Infocabinet.heure_ouverture/heure_fermeture/duree_rdv_minutes`, défauts identiques au comportement précédent codé en dur (8h-18h, 10 min).
- **`RdvReminders.php`** : bug corrigé où l'envoi d'un rappel écrasait inconditionnellement `rdvConfirmer` à `'Rappel envoyé'`, détruisant le vrai statut du RDV (`'Confirmé'`/`'En cours'` perdu). Nouvelle colonne `rendezvous.date_dernier_rappel` dédiée au tracking d'envoi ; le bouton "Rappeler" déclenche maintenant un vrai envoi via `WhatsAppService` (job `SendWhatsAppMessage`, `ShouldQueue`) au lieu d'ouvrir un lien `wa.me` côté navigateur. Les RDV historiques déjà marqués `rdvConfirmer = 'Rappel envoyé'` ne sont pas corrigés rétroactivement (dette gelée).
- **Dette documentée** : pas d'horaires par médecin (hérite du cabinet) ; envois WhatsApp effectivement synchrones tant que `QUEUE_CONNECTION=sync` ; pas de vraie table `actes` avec durée par type d'acte (durée fixe par cabinet uniquement).
- **Convention** : tout nouvel appelant de `Rendezvou::hasConflict()`/`generateNextOrderNumber()`/`getCreneauxDisponibles()`/`getProchainCreneauPropose()` en contexte non authentifié doit passer `$cabinetId` explicitement — ces méthodes lèvent `InvalidArgumentException` si le cabinet ne peut être résolu ni depuis le paramètre ni depuis `Auth::user()`.

### Application mobile légère praticiens — détail

- **PWA** (Progressive Web App) sous `/mobile/*`, réutilisant l'app Laravel/Livewire existante — pas d'app native. Layout dédié `resources/views/layouts/mobile.blade.php` (bottom nav, meta tags PWA), lien "Version mobile" ajouté au menu utilisateur du layout desktop.
- **Socle PWA** : `public/manifest.json`, `public/sw.js` (cache-first sur une whitelist stricte d'assets statiques, network-first + fallback `public/offline.html` pour la navigation), `public/icons/*` — gérés manuellement en dehors du pipeline Laravel Mix (pas de migration vers Vite pour ce chantier). Scope du service worker volontairement limité à `/mobile/`, zéro risque d'interférence avec le reste de l'app desktop.
- **Icônes placeholder** : les icônes PWA actuelles (`public/icons/icon-*.png`) sont générées programmatiquement (texte "SM" sur fond bleu) — à remplacer par un vrai jeu d'icônes de marque avant mise en avant commerciale de la PWA.
- **Nouveau composant `AgendaSemaine`** (`app/Http/Livewire/AgendaSemaine.php`) : vue semaine avec navigation, lecture + actions démarrer/terminer réutilisant la même logique que `SalleAttente` (copie assumée, pas d'héritage — extraire en trait `HandlesRdvLifecycle` si un 3e consommateur apparaît). Différence volontaire avec `SalleAttente` : les RDV `'Terminé'` restent visibles (l'agenda montre l'historique de la semaine), seuls les `'Annulé'` sont exclus. Pas de création de RDV en V1 (reste sur `CreateRendezVous` existant).
- **Salle d'attente/soins réutilisées sans duplication** : `SalleAttente.php`/`SalleSoins.php` gagnent un paramètre `modeMobile` (défaut `false`) qui bascule uniquement la vue rendue (`salle-attente-mobile.blade.php`/`salle-soins-mobile.blade.php`), aucune logique métier dupliquée ni modifiée. Convention pour la suite : tout nouveau composant Livewire ayant besoin d'une variante mobile suit ce patron plutôt que de dupliquer le composant.
- **Dette documentée** : pas de notification push cross-poste réelle (mise à jour via `wire:poll.15s` uniquement, délai perçu jusqu'à 15s entre postes — jugé acceptable, pas une urgence vitale) ; Web Push (VAPID) explicitement hors scope, chantier futur si ce délai s'avère insuffisant en usage réel ; manifest/icônes génériques, pas de branding par cabinet ; paiement mobile local reporté à un chantier séparé.

---

## Audit des modèles Eloquent (2026-08-27)

Audit exhaustif des ~65 modèles `app/Models/*.php` sous l'angle bonnes pratiques Laravel/Eloquent. Corrections appliquées immédiatement (par ordre de commit) :

- **Faille d'isolation tenant** : `TFournisseurPersonnel` avait une colonne `fkidcaibnet` (faute de frappe en base, ni `fkidCabinet` ni `fkidcabinet`) sans aucun trait tenant — invisible à toute recherche standard. Ajout de `BelongsToTenant` avec `$tenantColumn` pointant explicitement sur le nom réel (conservé tel quel, pas de renommage de colonne). Modèle confirmé mort (0 référence), corrigé par précaution avant une éventuelle réactivation.
- **Relation cabinet erronée** : `StockMedicament::cabinet()` pointait vers le modèle `Cabinet` (mort) au lieu d'`Infocabinet` (la vraie table tenant) — corrigé.
- **Garde-fou `BelongsToTenant`** : ajout d'une vérification `Schema::hasColumn()` en environnement non-production qui échoue bruyamment à la création si `$tenantColumn` n'est pas déclaré et que la colonne par défaut (`fkidCabinet`) n'existe pas réellement — évite qu'un futur modèle avec une colonne en casse différente ne remplisse silencieusement un attribut fantôme jamais persisté ni filtré.
- **Casts booléens corrigés** : `Masquer`/`IsImprimer`/`IsSupprimer` étaient castés `int` sur 5 modèles (`Acte`, `Fichetraitement`, `LotMedicament`, `StockMedicament`) — passés en `boolean`. `Ordonnanceref::statutSoin` (énuméré `en_attente|en_cours|termine`) était absent des `$casts` malgré sa présence en `$fillable` — ajouté en `string`.
- **Pseudo-relations mortes trompeuses supprimées** : `TUser::rendezVous()`/`factures()` portaient un nom de relation Eloquent mais exécutaient une requête immédiate (`->get()`) au lieu de retourner une vraie relation différée — aucun appelant trouvé, supprimées.
- **Relation `LotMedicament::user()` corrigée** : utilisait `'id'` comme clé locale au lieu de `'Iduser'` (vraie PK de `t_user`) — n'aurait jamais pu se résoudre correctement.
- **20 modèles morts supprimés** : `Currentuser`, `CurrentuserEnregistrement`, `TCampagnie`, `TCategorieCompteClient`, `TCurrentexercice`, `TExercice`, `TSouscompte`, `TTypeOperation`, `TArretCompte`, `TArretSituationCompte`, `TSoldeCaisse`, `TBanque`, `SoldeParJour`, `Periode`, `Problème`, `Typerecettesdepense`, `Typereglement`, `Ficheaimprimerautrefoi`, `Pjconvention`, `Factureaimprimer` — résidus de la génération Reliese initiale, zéro référence externe confirmée par grep exhaustif. `Cabinet` (déjà connu comme mort, 2 références résiduelles) volontairement non touché ici.
- **SoftDeletes sur `Facture`/`Reglement`/`CaisseOperation`** : aligne ces 3 tables financières sur la politique déjà en place sur les 6 tables santé. Corrigé au passage `MedecinFinanceService::getRecettes()`/`getStatistiques()` (jointures manuelles qui ne bénéficiaient pas automatiquement du scope SoftDeletes d'un modèle joint) pour exclure explicitement les factures annulées des calculs de chiffre d'affaires par médecin. `PatientManager::deletePatient()` reste volontairement en suppression physique pour les factures lors d'une purge complète de patient (décision Phase 1.5 déjà actée, un test existant le vérifie).

**Dette documentée, non traitée (risque jugé trop élevé pour une correction automatique)** :
- **Montants financiers en `DOUBLE` réel en base** (pas juste un cast Eloquent `float` — vérifié dans les migrations de création) sur `facture`, `caisse_operations`, `reglements`, `detailfacturepatient`, `acte`, `bordereauxfactures`, `consommables`. Une vraie correction nécessiterait un `ALTER TABLE ... DECIMAL(x,y)` sur des tables financières déjà en production, combiné à une réécriture de la logique arithmétique PHP dans `ReglementFacture.php` (calculs en cascade de `TotFacture`/`TotalPEC`/`TotalfactPatient`/`TotReglPatient` via `+`/`-`/`*`/`min()`/`max()` sur des floats natifs — un cast `decimal` renvoie des strings côté Eloquent, et aucun usage de `bcmath` n'existe dans le projet pour sécuriser ces calculs). Aucune preuve d'imprécision réellement constatée en production (pas de jeu de données permettant de le vérifier). **Chantier à traiter séparément avec validation métier explicite avant toute exécution, pas en correction d'audit automatique.**
- Duplication `User`/`TUser` (même table `t_user`, relations divergentes, casse différente `typeuser`/`typeUser`) — déjà documentée comme dette de longue date, non retraitée ici (chantier de réconciliation à part entière).
- Modèle `Cabinet` mort avec 2 références résiduelles à vérifier avant suppression définitive.
- Autres opportunités mineures relevées mais non corrigées (pas d'urgence) : `$timestamps` non exploité sur des colonnes `DtCr`/`DtAjout` existantes (pourrait utiliser `const CREATED_AT`), quelques accesseurs dupliquant l'API du modèle (`Acte::getActeNomAttribute()`), risque de requête N+1 sur `Detailfacturepatient::getLibelleAttribute()` si consommé sans `with()` préalable.

---

## Vue d'ensemble

| Phase | Objectif | Effort cumulé approx. | Condition de sortie |
|---|---|---|---|
| 0 | Sécurisation | ~5-7 semaines | Isolation tenant garantie, stack maintenu, tests sur workflow critique |
| 1 | Fondations SaaS | ~5-6 semaines | Un cabinet peut s'inscrire et payer seul |
| 2 | Qualité produit | ~4-5 semaines | Design cohérent, accessibilité de base, i18n propre |
| 3 | Différenciateurs | ~6-8 semaines | Avantage compétitif construit sur base saine |

Ces durées supposent une équipe de 1-2 développeurs à plein temps ; à ajuster selon les ressources réellement allouées. Les phases 2 et 3 peuvent être partiellement parallélisées entre elles une fois la Phase 1 terminée, mais **la Phase 0 doit être terminée avant tout le reste**.
