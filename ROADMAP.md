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

### 2.2 Feedback utilisateur — **S**
- Généraliser `wire:loading` sur les actions asynchrones (priorité : `AccueilPatient` et ses modals).
- Introduire un système de toast non bloquant pour remplacer les flash messages classiques.

### 2.3 Accessibilité de base — **S**
- `aria-live` sur la file d'attente du portail patient (remplace le `location.reload()` toutes les 30s par un vrai polling Livewire).
- `tabindex`/gestion de focus sur les modals, redondance texte+couleur sur les badges de statut.

### 2.4 i18n structurée — **M**
- Remplacer le bilinguisme ad hoc (champs `ArabText` dédiés) par un vrai système de traduction Laravel, prérequis avant toute expansion géographique/linguistique.

**Sortie de phase** : produit plus robuste et cohérent visuellement, prêt à scaler en nombre de vues/fonctionnalités.

---

## Phase 3 — Différenciateurs commerciaux

Objectif : construire l'avantage compétitif une fois le socle sécurisé et vendable.

- **Paiement mobile local intégré** (Bankily, Masrvi, Orange Money) dans le règlement/caisse — **M**
- **Portail patient enrichi** : prise de RDV en ligne, rappels SMS/WhatsApp — **M**
- **Module tiers-payant avancé** : bordereaux automatiques groupés par assureur — **M**
- **Application mobile légère praticiens** (agenda, salle d'attente/soins temps réel) — **L**

Ces chantiers sont volontairement en dernier : ils apportent de la valeur commerciale mais n'ont pas de sens à construire sur une fondation multi-tenant non sécurisée.

---

## Vue d'ensemble

| Phase | Objectif | Effort cumulé approx. | Condition de sortie |
|---|---|---|---|
| 0 | Sécurisation | ~5-7 semaines | Isolation tenant garantie, stack maintenu, tests sur workflow critique |
| 1 | Fondations SaaS | ~5-6 semaines | Un cabinet peut s'inscrire et payer seul |
| 2 | Qualité produit | ~4-5 semaines | Design cohérent, accessibilité de base, i18n propre |
| 3 | Différenciateurs | ~6-8 semaines | Avantage compétitif construit sur base saine |

Ces durées supposent une équipe de 1-2 développeurs à plein temps ; à ajuster selon les ressources réellement allouées. Les phases 2 et 3 peuvent être partiellement parallélisées entre elles une fois la Phase 1 terminée, mais **la Phase 0 doit être terminée avant tout le reste**.
