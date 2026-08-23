# Audit complet — Jasminsv2 (Cabinet Savwa/Jasmins)

**Date** : 2026-08-23
**Périmètre** : architecture système, UX/UI, potentiel business & transformation SaaS multi-tenant
**Méthode** : revue de code statique (composer.json/package.json, migrations, routes, contrôleurs, composants Livewire, vues Blade) + lecture de PRD.md, FONCTIONNALITES.md, APP_DOCUMENTATION.md

---

## Résumé exécutif

Jasminsv2 est une application de gestion de cabinet dentaire/médical fonctionnellement mature (workflow complet RDV → consultation → ordonnance → facture → règlement → caisse, gestion de stock pharmacie, tiers-payant assurance, portail patient QR code bilingue). Le produit a de la valeur métier réelle et un positionnement différenciant sur un marché francophone (Mauritanie/Afrique de l'Ouest) peu équipé.

Mais l'application a été construite comme un **outil mono-tenant sur mesure**, pas comme un produit. Trois constats structurent tout le reste de cet audit :

1. **La base de données est "tenant-shaped" mais pas tenant-safe.** La colonne `fkidCabinet` existe presque partout, mais rien ne garantit l'isolation — c'est un filtre applicatif de confort, pas une frontière de sécurité. C'est le risque n°1 avant toute commercialisation à plusieurs clients.
2. **Il n'existe aucune des briques d'un produit SaaS** : pas de couche admin plateforme, pas de facturation d'abonnement, pas d'onboarding self-service, pas de tests.
3. **Le stack technique est en fin de vie** (Laravel 8, EOL depuis janvier 2024) — un chantier de mise à niveau est un prérequis, pas une option différée.

**Verdict** : le socle métier justifie l'investissement dans une transformation SaaS, mais celle-ci doit être précédée d'un chantier de sécurisation (isolation tenant, upgrade Laravel, tests) avant tout démarchage commercial multi-clients.

---

## 1. Audit d'architecture technique

### 1.1 Qualité du code / dette technique

- **Logique métier dans les routes** : `routes/web.php` contient des requêtes Eloquent complexes directement dans des closures (`/api/salle-soins/count` l.38-50, `/api/stock/alerts` l.52-69, `/api/salle-attente/count` l.71-85) — aucun contrôleur ni service dédié.
- **Composants Livewire god-object** : `PharmacieManager.php` (1313 lignes), `ReglementFacture.php` (1249), `AccueilPatient.php` (1004), `ConsultationForm.php` (712), `OrdonnanceManager.php` (665). Total ~10 755 lignes sur 33 composants, très inégalement distribuées — signe de composants mélangeant UI, validation et règles métier.
- **Tests : 0% de couverture réelle.** Le dossier `tests/` ne contient que le boilerplate par défaut de Laravel (aucun test métier écrit).

### 1.2 Sécurité

- **Pas de Policies Laravel.** Les autorisations reposent sur un middleware maison (`CheckPermission`) et des appels `hasPermission()` dispersés jusque dans les routes — pas de couche d'autorisation centralisée et testable.
- **SQL brut limité et à surveiller** : `DB::raw`/`whereRaw` dans `MedecinFinanceService.php`, `MedecinPaiementStats.php`, `PharmacieManager.php`, `ActesPatient.php` — pas d'injection directe visible (agrégations SUM/DATE), mais absence de binding paramétré explicite dans certains `whereRaw` (`PharmacieManager.php:727`) à corriger par prudence.
- **Validation inline uniquement** — pas de Form Requests, ce qui est acceptable en Livewire mais empêche la réutilisation des règles.
- **Hashing des mots de passe correct** (`Hash::make()` dans `TUser.php`). CSRF géré nativement par Livewire/Blade. Aucun secret en dur détecté.
- **Point critique déjà identifié dans APP_DOCUMENTATION.md** : logs contenant des données patients en clair — à corriger avant toute mise en production multi-tenant (violation immédiate en cas d'audit de conformité).

### 1.3 Performance / scalabilité

- **Cache** utilisé ponctuellement et bien fait (`AccueilPatient.php`, `CaisseOperationsManager.php`) mais non généralisé.
- **Eager loading sous-utilisé** : seulement 22 occurrences de `::with(` sur 33 composants Livewire — risque de N+1 non détecté ailleurs.
- **Aucune queue** : tout est synchrone (PDF, calculs statistiques lourds) — pénalisera la scalabilité sous charge multi-tenant.
- **Index `fkidCabinet` incohérents** : présents sur `medecins`, `patients`, `t_user`, `dossier_medical`, `analyses_patient`, mais **absents** sur `facture`, `caisse_operations`, `ordonnanceref`, `boncommande`, `bordereauxfactures` — alors que ce champ sert de filtre systématique. Problème de performance certain à volumétrie croissante.

### 1.4 Fiabilité / exploitabilité

- Logging Laravel par défaut, aucun canal de monitoring externe (Sentry/Bugsnag).
- `Exceptions/Handler.php` quasi vide — aucune gestion d'erreur personnalisée.
- **Aucun CI/CD réel** (seul un workflow GitHub Actions de cherry-pick existe) — pas de tests/build/déploiement automatisés.
- Aucun environnement de staging documenté.

### 1.5 Dépendances

- **Laravel 8.75 : End-of-Life depuis janvier 2024** — plus de correctifs de sécurité. C'est le risque technique le plus urgent du projet.
- `laravel/sanctum ^2.11` également une branche ancienne.
- **Laravel Mix** (fin de vie, remplacé par Vite depuis Laravel 9+) combiné à **Tailwind v4** — incohérence d'écosystème front.

### 1.6 Documentation

`APP_DOCUMENTATION.md` (902 lignes), `FONCTIONNALITES.md` (242 lignes), `PRD.md` (124 lignes) — volume correct, mais probablement **en retard sur le code réel** : ces documents datent de mai/juillet alors que des migrations plus récentes (avril 2026 : dossier médical, analyses patient) ne semblent pas y être pleinement reflétées.

---

## 2. Audit UX/UI

### 2.1 Cohérence visuelle

Design system quasi inexistant : `resources/views/components/` ne contient que `logo.blade.php` et `modal.blade.php` pour 79 vues et 33 composants. Le mapping statut → couleur (ex. "En attente" = jaune, "En cours" = vert) est **recopié en dur avec un switch PHP dans chaque vue** — toute évolution de charte graphique nécessite N corrections manuelles. Mélange résiduel de classes Bootstrap legacy et de CSS inline dans les vues d'impression.

### 2.2 Accessibilité

Très faible : `aria-*` présent dans seulement 6 fichiers sur 79, `alt=` dans 13, **`tabindex` totalement absent** (aucune gestion de focus dans les 20 modals du hub AccueilPatient). Statuts encodés uniquement par couleur, sans redondance texte/icône — problématique pour le daltonisme. Le portail patient bilingue pose `dir="rtl"` au niveau `<html>` global alors qu'il mélange du contenu français, cassant l'alignement naturel de ce texte.

### 2.3 Responsive

Seulement 38 fichiers sur 79 utilisent des breakpoints Tailwind (`sm:/md:/lg:`) — cohérent pour un back-office pensé desktop/tablette, mais le **portail patient public (consulté via QR code, donc majoritairement mobile) n'est que partiellement responsive**, ce qui est son cas d'usage principal.

### 2.4 Ergonomie du hub AccueilPatient (20 modals)

`AccueilPatient.php` (1004 lignes) et sa vue (865 lignes) orchestrent ~26 états de modals dans un seul composant Livewire — pattern à risque : empilement d'états booléens difficile à tracer, re-renders potentiellement larges. **Feedback de chargement quasi absent** : seulement 11 fichiers sur 79 utilisent `wire:loading`, ce qui expose à des doubles clics/doubles soumissions pendant les temps de latence.

### 2.5 Gestion des erreurs côté UI

`@error()` présent dans seulement 18 fichiers sur un nombre de formulaires bien plus élevé. **Aucune librairie de toast/notification non bloquante** — les confirmations reposent sur des flash messages Laravel classiques, moins adaptés à un contexte Livewire où l'utilisateur reste sur la même page.

### 2.6 Impression

Point fort relatif : 16 fichiers dédiés à l'impression, tous avec `@media print`. Le correctif récent (`* { color: #000 !important; }`) fonctionne mais révèle une **approche réactive** (corrections après coup) plutôt qu'une feuille de style d'impression pensée dès le départ — pas d'usage des classes utilitaires `print:` de Tailwind.

### 2.7 Portail patient public

UX globalement soignée pour un usage mobile simple, mais avec une **dette de traduction visible** (mélange français non traduit dans des blocs censés être en arabe) et une file d'attente en temps réel qui utilise `location.reload()` toutes les 30 secondes plutôt qu'un vrai polling Livewire — flash visuel et perte de scroll à chaque rafraîchissement. Aucun `aria-live` pour signaler les mises à jour automatiques, alors que c'est précisément le cas d'usage qui en bénéficierait le plus.

---

## 3. Audit business & potentiel SaaS

### 3.1 Proposition de valeur actuelle

Le workflow cœur (RDV/consultation/facture) est **standard**, non différenciant face à des solutions établies. Les vrais atouts :
- **Gestion de stock pharmacie intégrée** au workflow clinique (lots, péremption, déduction automatique via ordonnance urgence) — rare dans les logiciels génériques.
- **Tiers-payant/assurance natif** (taux de PEC par assureur, répartition automatique) — critique sur ce marché.
- **Portail patient QR code sans compte** — UX à friction minimale, adaptée à des patients peu équipés.
- **Bilinguisme français/arabe amorcé** — absent des solutions occidentales importées.

### 3.2 Marché cible

- **Cœur de cible** : cabinets dentaires/médicaux indépendants et petits groupes (1-5 praticiens) en Mauritanie et zone francophone Afrique de l'Ouest/Maghreb — segment aujourd'hui sous-équipé.
- **Cible secondaire** : petites chaînes/cliniques multi-sites (2-10 sites), où `fkidCabinet` devient un atout réel s'il est exploité pour du multi-site groupé.
- **Secteur public** : opportunité tardive (cycles d'achat longs, exigences d'hébergement souverain), pas une cible de lancement.
- Marché de niche géographique mais avec **peu de concurrence logicielle localisée** — la barrière est la capacité à vendre/déployer/supporter à distance, pas la taille du marché.

### 3.3 Modèle de revenu recommandé

**Abonnement par cabinet** (pas par utilisateur), en paliers fonctionnels :
- **Essentiel** : patients, RDV, consultation, facture simple, caisse.
- **Standard** : + ordonnances, dossier médical, portail patient QR.
- **Pro/Premium** : + stock pharmacie multi-lots, tiers-payant assurance, statistiques multi-médecins, multi-site.

Tarification différenciée par nombre de médecins actifs plutôt que par utilisateur total. Éviter le freemium pur (données de santé + support nécessaires dès le premier usage) ; préférer un **essai gratuit encadré (14-30 jours)** avec migration de données assistée, la friction d'onboarding étant le vrai frein à l'essai.

### 3.4 Gaps critiques avant commercialisation multi-tenant

- **Isolation des données non garantie** — risque n°1, à traiter avant tout.
- Aucune brique de **facturation d'abonnement** (Stripe, paiement mobile local).
- **Aucun onboarding self-service** (création de cabinet, import patients existants).
- **Aucune couche admin plateforme** (super-admin multi-cabinets, suspension, usage).
- Pas de gestion de **quotas/sauvegardes par tenant**.
- Pas de **multi-devise** ni d'**i18n structurée** (le bilinguisme actuel est ad hoc, via champs dédiés).
- **Suppression cascade irréversible** des patients/factures (documentée dans FONCTIONNALITES.md) — incompatible avec les obligations de conservation des données de santé.

### 3.5 Risques et conformité

Les données manipulées (dossier médical, allergies, antécédents) sont des **données de santé**, catégorie sensible sous quasi tout cadre légal. Implications :
- Obligation de minimisation et durée de conservation définie — la suppression cascade actuelle (tout ou rien) n'y répond pas.
- **Logs en clair contenant des données patients** — violation immédiate en cas d'audit, à corriger en priorité.
- **Risque réputationnel disproportionné** : une seule fuite croisée entre deux cabinets clients détruirait la confiance sur un marché où le bouche-à-oreille est le principal canal d'acquisition.
- Nécessité de contrats clients clarifiant l'hébergement des données (la souveraineté des données est souvent une exigence de vente en Afrique de l'Ouest).

### 3.6 Différenciateurs à construire

1. **Portail patient enrichi** : prise de RDV en ligne, rappels SMS/WhatsApp, paiement mobile money — extension naturelle de l'existant.
2. **Module tiers-payant avancé** : génération automatique de bordereaux groupés pour les assureurs.
3. **Application mobile légère praticiens** : agenda, salle d'attente/soins en temps réel.
4. **Interopérabilité paiement mobile local** (Bankily, Masrvi, Orange Money) dans le règlement et la caisse — différenciateur structurant, absent des solutions importées.

---

## 4. Synthèse des priorités

| Priorité | Chantier | Pourquoi |
|---|---|---|
| **P0** | Isolation tenant systématique (global scope + middleware) | Risque de fuite de données entre cabinets clients — bloquant absolu avant toute vente multi-clients |
| **P0** | Retirer les données patients en clair des logs | Non-conformité immédiate, risque légal |
| **P0** | Upgrade Laravel 8 → 10/11 | Faille de sécurité non patchée depuis 2024 |
| **P1** | Couche admin plateforme (super-admin) | Prérequis opérationnel pour gérer plusieurs cabinets clients |
| **P1** | Facturation d'abonnement + onboarding self-service | Prérequis commercial pour vendre en SaaS |
| **P1** | Index DB manquants sur `fkidCabinet` (facture, caisse, ordonnances) | Performance à l'échelle multi-tenant |
| **P2** | Tests automatisés sur le workflow critique (facturation, règlement) | Fiabilité avant d'ouvrir à des clients externes |
| **P2** | Design system minimal (statuts, boutons, badges) | Réduit la dette UI et accélère les évolutions futures |
| **P2** | Accessibilité de base (tabindex, aria-live sur le portail patient) | Bonne pratique, impact modéré à court terme |
| **P3** | Différenciateurs (paiement mobile, portail enrichi, appli mobile) | Valeur ajoutée compétitive, une fois le socle sécurisé |

**Recommandation** : ne pas vendre à un deuxième cabinet avant d'avoir traité les points P0. Le potentiel produit est réel, mais la fondation actuelle est celle d'un logiciel sur mesure, pas encore celle d'un SaaS.
