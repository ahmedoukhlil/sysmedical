<div class="w-full px-3 sm:px-4 md:px-6 lg:px-8 max-w-7xl mx-auto mt-4 md:mt-8">
@php $u = Auth::user(); @endphp

    {{-- Bannière de bienvenue --}}
    <div class="mb-4 md:mb-6 p-4 md:p-6 rounded-xl bg-primary text-white shadow-lg flex items-center justify-between gap-4">
        <div>
            @php
                $nomCab = Auth::user()->cabinet->NomCabinet ?? null;
                $excluded = ['Cabinet Savwa', 'Cabinet Medical Savwa', 'Cabinet savwa', 'SysMedical'];
                if (!$nomCab || in_array($nomCab, $excluded)) $nomCab = 'SysMedical';
            @endphp
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold mb-0.5 leading-tight">{{ $nomCab }}</h1>
            <p class="text-white/80 text-xs sm:text-sm md:text-base">
                {{ is_array(Auth::user()->typeuser) ? (Auth::user()->typeuser['Libelle'] ?? '') : (is_object(Auth::user()->typeuser) ? Auth::user()->typeuser->Libelle : Auth::user()->typeuser) }}
                — <span class="font-bold">{{ Auth::user()->NomComplet ?? Auth::user()->name ?? '' }}</span>
            </p>
        </div>
        <i class="fas fa-staff-snake text-4xl md:text-5xl opacity-20 flex-shrink-0"></i>
    </div>

    {{-- Recherche patient + actions rapides --}}
    @if($u->hasPermission('patient.view'))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 md:p-5 flex flex-col lg:flex-row items-stretch lg:items-center gap-3 mb-4 md:mb-6">
        <div class="w-full lg:flex-1">
            <livewire:patient-search />
        </div>
        <div class="flex gap-2 flex-shrink-0">
            @if($u->hasPermission('patient.view'))
            <button wire:click="openGestionPatientsModal" wire:loading.attr="disabled" wire:target="openGestionPatientsModal" class="btn-secondary text-sm px-4 py-2.5 justify-center whitespace-nowrap">
                <i class="fas fa-users" wire:loading.class="opacity-0" wire:target="openGestionPatientsModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="openGestionPatientsModal"></span>
                Liste patients
            </button>
            @endif
            @if($u->hasPermission('rendez-vous.view'))
            <button wire:click="showCreateRdv" wire:loading.attr="disabled" wire:target="showCreateRdv" class="btn-secondary text-sm px-4 py-2.5 justify-center whitespace-nowrap relative">
                <i class="fas fa-calendar-plus" wire:loading.class="opacity-0" wire:target="showCreateRdv"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="showCreateRdv"></span>
                Gestion RDV
                @if($rdvARappelerCount > 0)
                    <span class="absolute -top-2 -right-2 inline-flex items-center justify-center min-w-[1.2rem] h-5 px-1 rounded-full bg-red-500 text-white text-xs font-bold shadow">
                        <i class="fas fa-bell text-xs mr-0.5"></i>{{ $rdvARappelerCount }}
                    </span>
                @endif
            </button>
            @endif
        </div>
    </div>
    @endif

    {{-- Barre de navigation principale --}}
    <div class="flex flex-wrap gap-2 mb-4 py-3 px-3 md:px-4 justify-center rounded-xl bg-white border border-gray-100 shadow-sm">

        {{-- Gestion du patient (visible si accès patient) --}}
        @if($u->hasPermission('patient.view'))
        <button wire:click="togglePatientMenu" wire:loading.attr="disabled" wire:target="togglePatientMenu"
            @if(!$selectedPatient) disabled title="Sélectionnez un patient d'abord" @endif
            class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center
                {{ $showPatientMenu ? '!bg-primary !text-white !border-primary' : '' }}
                {{ !$selectedPatient ? 'opacity-50 cursor-not-allowed' : '' }}">
            <i class="fas fa-user-friends" wire:loading.class="opacity-0" wire:target="togglePatientMenu"></i>
            <span>Gestion du patient</span>
            <i class="fas fa-chevron-{{ $showPatientMenu ? 'up' : 'down' }} text-xs opacity-60"></i>
        </button>
        @endif

        {{-- Caisse Paie --}}
        @if($u->hasPermission('caisse-operations.view'))
        <button wire:click="showCaisseOperations" wire:loading.attr="disabled" wire:target="showCaisseOperations" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
            <i class="fas fa-cash-register" wire:loading.class="opacity-0" wire:target="showCaisseOperations"></i>
            <span class="spinner spinner-dark" wire:loading wire:target="showCaisseOperations"></span>
            <span>Caisse Paie</span>
        </button>
        @endif

        {{-- Dépenses --}}
        @if($u->hasPermission('depenses.view'))
        <button wire:click="openDepenses" wire:loading.attr="disabled" wire:target="openDepenses" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
            <i class="fas fa-receipt" wire:loading.class="opacity-0" wire:target="openDepenses"></i>
            <span class="spinner spinner-dark" wire:loading wire:target="openDepenses"></span>
            <span>Dépenses</span>
        </button>
        @endif

        {{-- Statistiques --}}
        @if($u->hasPermission('statistiques.view'))
        <button wire:click="showStatistiques" wire:loading.attr="disabled" wire:target="showStatistiques" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
            <i class="fas fa-chart-bar" wire:loading.class="opacity-0" wire:target="showStatistiques"></i>
            <span class="spinner spinner-dark" wire:loading wire:target="showStatistiques"></span>
            <span>Statistiques</span>
        </button>
        @endif

        {{-- Salle d'attente --}}
        @if($u->hasPermission('salle-attente.view'))
        <div class="relative">
            <button wire:click="ouvrirSalleAttente" wire:loading.attr="disabled" wire:target="ouvrirSalleAttente"
                class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center
                    {{ $showSalleAttenteModal ? '!bg-primary !text-white !border-primary' : '' }}">
                <i class="fas fa-couch" wire:loading.class="opacity-0" wire:target="ouvrirSalleAttente"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirSalleAttente"></span>
                <span>Salle d'attente</span>
            </button>
            <span id="salle-attente-badge"
                  class="hidden absolute -top-2.5 -right-2.5 items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-red-500 text-white text-xs font-bold shadow-lg z-10 pointer-events-none">
                <i class="fas fa-bell text-[9px]"></i><span id="salle-attente-count">0</span>
            </span>
        </div>
        @endif

        {{-- Salle de soins (infirmiers) --}}
        @if($u->hasPermission('salle-soins.view'))
        <div class="relative">
            <button wire:click="ouvrirSalleSoins" wire:loading.attr="disabled" wire:target="ouvrirSalleSoins"
                class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center
                    {{ $showSalleSoinsModal ? '!bg-primary !text-white !border-primary' : '' }}">
                <i class="fas fa-syringe" wire:loading.class="opacity-0" wire:target="ouvrirSalleSoins"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirSalleSoins"></span>
                <span>Salle de soins</span>
            </button>
            <span id="salle-soins-badge"
                  class="hidden absolute -top-2.5 -right-2.5 items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-red-500 text-white text-xs font-bold shadow-lg z-10 pointer-events-none">
                <i class="fas fa-bell text-[9px]"></i><span id="salle-soins-count">0</span>
            </span>
        </div>
        @endif

        {{-- Gestion du cabinet --}}
        @if($u->hasAnyPermission(['medecin.view','assureur.view','act.view','stock.view','pharmacie.view','user.view','medecin.manage']))
        <button wire:click="toggleCabinetMenu" wire:loading.attr="disabled" wire:target="toggleCabinetMenu"
            class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center
                {{ $showCabinetMenu ? '!bg-primary !text-white !border-primary' : '' }}">
            <i class="fas fa-cogs" wire:loading.class="opacity-0" wire:target="toggleCabinetMenu"></i>
            <span>Gestion du cabinet</span>
            <i class="fas fa-chevron-{{ $showCabinetMenu ? 'up' : 'down' }} text-xs opacity-60"></i>
        </button>
        @endif
    </div>

    {{-- Sous-menu Gestion du patient --}}
    @if($selectedPatient && $showPatientMenu)
    <div class="patient-menu-container mb-4">
        <div class="patient-submenu flex flex-wrap gap-2 justify-center py-3 px-3 md:px-4 bg-blue-50/60 border border-primary/20 rounded-xl show" data-menu="patient">

            @if($u->hasPermission('consultation.view'))
            <button wire:click="showConsultation" wire:loading.attr="disabled" wire:target="showConsultation" type="button" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-stethoscope" wire:loading.class="opacity-0" wire:target="showConsultation"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="showConsultation"></span>
                <span>Consultation</span>
            </button>
            @endif

            @if($u->hasPermission('facture.view') || $u->hasPermission('facture.view.own'))
            <button wire:click="showReglement" wire:loading.attr="disabled" wire:target="showReglement" type="button" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-file-invoice-dollar" wire:loading.class="opacity-0" wire:target="showReglement"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="showReglement"></span>
                <span>Facture / Devis</span>
            </button>
            @endif

            @if($u->hasPermission('rendez-vous.view'))
            <button wire:click="showRendezVous" wire:loading.attr="disabled" wire:target="showRendezVous" type="button" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-calendar-check" wire:loading.class="opacity-0" wire:target="showRendezVous"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="showRendezVous"></span>
                <span>Rendez-vous</span>
            </button>
            @endif

            @if($u->hasPermission('ordonnance.create'))
            <button wire:click="ouvrirUrgenceModal" wire:loading.attr="disabled" wire:target="ouvrirUrgenceModal" type="button" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-bolt" wire:loading.class="opacity-0" wire:target="ouvrirUrgenceModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirUrgenceModal"></span>
                <span>Trt. Urgence</span>
            </button>
            <button wire:click="ouvrirOrdonnanceModal" wire:loading.attr="disabled" wire:target="ouvrirOrdonnanceModal" type="button" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-file-prescription" wire:loading.class="opacity-0" wire:target="ouvrirOrdonnanceModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirOrdonnanceModal"></span>
                <span>Ordonnances</span>
            </button>
            @endif

            @if($u->hasPermission('dossier.view'))
            <button wire:click="showDossierMedical" wire:loading.attr="disabled" wire:target="showDossierMedical" type="button" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-folder-open" wire:loading.class="opacity-0" wire:target="showDossierMedical"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="showDossierMedical"></span>
                <span>Dossier médical</span>
            </button>
            @endif

            @if($u->hasPermission('consultation.create'))
            <button wire:click="ouvrirActesPatientModal" wire:loading.attr="disabled" wire:target="ouvrirActesPatientModal" type="button" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-procedures" wire:loading.class="opacity-0" wire:target="ouvrirActesPatientModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirActesPatientModal"></span>
                <span>Actes à effectuer</span>
            </button>
            @endif
        </div>
    </div>
    @endif

    {{-- Sous-menu Gestion du cabinet --}}
    @if($showCabinetMenu)
    <div class="mb-4">
        <div class="flex flex-wrap gap-2 justify-center py-3 px-3 md:px-4 bg-gray-50 border border-gray-200 rounded-xl" data-menu="cabinet">

            @if($u->hasPermission('assureur.view'))
            <button wire:click="ouvrirAssureurModal" wire:loading.attr="disabled" wire:target="ouvrirAssureurModal" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-shield-alt" wire:loading.class="opacity-0" wire:target="ouvrirAssureurModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirAssureurModal"></span>
                Assurances
            </button>
            @endif

            @if($u->hasPermission('act.view'))
            <button wire:click="ouvrirListeActesModal" wire:loading.attr="disabled" wire:target="ouvrirListeActesModal" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-list-alt" wire:loading.class="opacity-0" wire:target="ouvrirListeActesModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirListeActesModal"></span>
                Actes / Soins
            </button>
            @endif

            @if($u->hasPermission('pharmacie.view'))
            <button wire:click="ouvrirListeMedicamentsModal" wire:loading.attr="disabled" wire:target="ouvrirListeMedicamentsModal" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-pills" wire:loading.class="opacity-0" wire:target="ouvrirListeMedicamentsModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirListeMedicamentsModal"></span>
                Médicaments
            </button>
            @endif

            @if($u->hasPermission('medecin.view'))
            <button wire:click="ouvrirMedecinsModal" wire:loading.attr="disabled" wire:target="ouvrirMedecinsModal" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-user-md" wire:loading.class="opacity-0" wire:target="ouvrirMedecinsModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirMedecinsModal"></span>
                Médecins
            </button>
            @endif

            @if($u->hasPermission('caisse-operations.view'))
            <button wire:click="ouvrirTypePaiementModal" wire:loading.attr="disabled" wire:target="ouvrirTypePaiementModal" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-credit-card" wire:loading.class="opacity-0" wire:target="ouvrirTypePaiementModal"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirTypePaiementModal"></span>
                Paiements
            </button>
            @endif

            @if($u->hasPermission('stock.view'))
            <div class="relative">
                <button wire:click="ouvrirDashboardStock" wire:loading.attr="disabled" wire:target="ouvrirDashboardStock" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                    <i class="fas fa-chart-line" wire:loading.class="opacity-0" wire:target="ouvrirDashboardStock"></i>
                    <span class="spinner spinner-dark" wire:loading wire:target="ouvrirDashboardStock"></span>
                    Suivi stock
                </button>
                {{-- Badge stock faible (orange) --}}
                <span id="stock-faible-badge"
                      class="hidden absolute -top-2.5 left-2 items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-orange-500 text-white text-xs font-bold shadow-lg z-10 pointer-events-none"
                      title="Produits en stock faible">
                    <i class="fas fa-bell text-[9px]"></i><span id="stock-faible-count">0</span>
                </span>
                {{-- Badge stock épuisé (rouge) --}}
                <span id="stock-epuise-badge"
                      class="hidden absolute -top-2.5 right-2 items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-red-500 text-white text-xs font-bold shadow-lg z-10 pointer-events-none"
                      title="Produits épuisés">
                    <i class="fas fa-bell text-[9px]"></i><span id="stock-epuise-count">0</span>
                </span>
            </div>
            @endif

            @if($u->hasPermission('user.view'))
            <a href="{{ route('users.index') }}" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-users-cog"></i> Utilisateurs
            </a>
            @endif

            @if($u->hasPermission('medecin.manage'))
            <button wire:click="ouvrirParametresCabinet" wire:loading.attr="disabled" wire:target="ouvrirParametresCabinet"
                class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center
                    {{ $showParametresCabinetModal ? '!bg-primary !text-white !border-primary' : '' }}">
                <i class="fas fa-sliders-h" wire:loading.class="opacity-0" wire:target="ouvrirParametresCabinet"></i>
                <span class="spinner spinner-dark" wire:loading wire:target="ouvrirParametresCabinet"></span>
                Paramètres
            </button>
            @endif
        </div>
    </div>
    @endif

    {{-- Composant HistoriquePaiement toujours présent --}}
    <livewire:historique-paiement wire:key="historique-paiement" lazy />


    {{-- ═══════════════════════════════════════════════════════
         MODAUX — pattern unifié : .modal-overlay / .modal-box
    ════════════════════════════════════════════════════════ --}}

    @php
$patientNom = $selectedPatient
    ? (is_array($selectedPatient)
        ? ($selectedPatient['NomPatient'] ?? $selectedPatient['Nom'] ?? 'Patient')
        : ($selectedPatient->NomPatient ?? $selectedPatient->Nom ?? 'Patient'))
    : '';
$patientId = $selectedPatient
    ? (is_array($selectedPatient) ? ($selectedPatient['ID'] ?? '') : ($selectedPatient->ID ?? ''))
    : '';
@endphp

{{-- ── GESTION DU PATIENT ── --}}

{{-- Consultation --}}
@if($showConsultation && $selectedPatient)
<div class="modal-overlay" wire:click.self="fermerConsultationModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-consultation">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-consultation"><i class="fas fa-stethoscope mr-2"></i>Consultation</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" wire:click="fermerConsultationModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:consultation-form wire:key="consultation-modal-{{ $patientId }}" :patient="$selectedPatient" />
        </div>
    </div>
</div>
@endif

{{-- Facture / Devis --}}
@if($showReglement && $selectedPatient)
<div class="modal-overlay" wire:click.self="fermerReglementModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-reglement">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-reglement"><i class="fas fa-file-invoice-dollar mr-2"></i>Facture / Devis</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" wire:click="fermerReglementModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:reglement-facture wire:key="reglement-modal-{{ $patientId }}" :selectedPatient="$selectedPatient" />
        </div>
    </div>
</div>
@endif

{{-- Rendez-vous patient --}}
@if($showRendezVous && $selectedPatient)
<div class="modal-overlay" wire:click.self="fermerRendezVousModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-rendezvous">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-rendezvous"><i class="fas fa-calendar-check mr-2"></i>Rendez-vous</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" wire:click="fermerRendezVousModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:create-rendez-vous wire:key="rendez-vous-modal-{{ $patientId }}" :patient="$selectedPatient" />
        </div>
    </div>
</div>
@endif

{{-- Traitement d'urgence --}}
@if($showUrgenceModal && $selectedPatient)
<div class="modal-overlay" wire:click.self="fermerUrgenceModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-urgence">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-urgence"><i class="fas fa-bolt mr-2"></i>Traitement d'urgence</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" wire:click="fermerUrgenceModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:ordonnance-manager wire:key="urgence-modal-{{ $patientId }}" :patient="$selectedPatient" :mode-force="'urgence'" />
        </div>
    </div>
</div>
@endif

{{-- Ordonnances --}}
@if($showOrdonnanceModal && $selectedPatient)
<div class="modal-overlay" wire:click.self="fermerOrdonnanceModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-ordonnance">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-ordonnance"><i class="fas fa-file-prescription mr-2"></i>Ordonnances</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" wire:click="fermerOrdonnanceModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:ordonnance-manager wire:key="ordonnance-modal-{{ $patientId }}" :patient="$selectedPatient" />
        </div>
    </div>
</div>
@endif

{{-- Dossier médical --}}
@if($showDossierMedical && $selectedPatient)
<div class="modal-overlay" wire:click.self="fermerDossierMedicalModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-dossier-medical">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-dossier-medical"><i class="fas fa-folder-open mr-2"></i>Dossier médical</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" wire:click="fermerDossierMedicalModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:dossier-medical-manager wire:key="dossier-medical-{{ $patientId }}" :patient="$selectedPatient" />
        </div>
    </div>
</div>
@endif

{{-- ── GESTION RDV (général) ── --}}

@if($showCreateRdvModal)
<div class="modal-overlay" wire:click.self="closeCreateRdvModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-create-rdv">
    <div class="modal-box max-w-6xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-create-rdv"><i class="fas fa-calendar-alt mr-2"></i>Gestion des Rendez-vous</h2>
            </div>
            <button type="button" wire:click="closeCreateRdvModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        {{-- Onglets --}}
        <div class="border-b border-gray-200 px-6 flex gap-6">
            <button wire:click="$set('activeRdvTab', 'create')"
                class="py-3 px-1 border-b-2 font-medium text-sm
                    {{ $activeRdvTab === 'create' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="fas fa-plus mr-1"></i> Gestion RDV
            </button>
            <button wire:click="$set('activeRdvTab', 'reminders')"
                class="py-3 px-1 border-b-2 font-medium text-sm relative
                    {{ $activeRdvTab === 'reminders' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="fas fa-bell mr-1"></i> Rappels RDV
                @if($rdvRemindersCount > 0)
                <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $rdvRemindersCount }}</span>
                @endif
            </button>
        </div>
        <div class="modal-body">
            <div id="modal-loading" class="hidden flex items-center justify-center py-8">
                <div class="spinner spinner-dark"></div>
                <span class="ml-3 text-gray-600 text-sm">Chargement...</span>
            </div>
            @if($activeRdvTab === 'create')
                <livewire:create-rendez-vous wire:key="create-rdv-modal" :patient="$selectedPatient" />
            @elseif($activeRdvTab === 'reminders')
                <livewire:rdv-reminders wire:key="rdv-reminders-modal" />
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── GESTION DES PATIENTS ── --}}

@if($showNouveauPatientModal)
<div class="modal-overlay" wire:click.self="closeNouveauPatientModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-nouveau-patient">
    <div class="modal-box max-w-3xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-nouveau-patient"><i class="fas fa-user-plus mr-2"></i>Nouveau patient</h2>
            <button type="button" wire:click="closeNouveauPatientModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:patient-manager wire:key="nouveau-patient-modal" :creationOnly="true" />
        </div>
    </div>
</div>
@endif

@if($showGestionPatientsModal)
<div class="modal-overlay" wire:click.self="closeGestionPatientsModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-gestion-patients">
    <div class="modal-box max-w-6xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-gestion-patients"><i class="fas fa-users mr-2"></i>Gestion des patients</h2>
            <button type="button" wire:click="closeGestionPatientsModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:patient-manager />
        </div>
    </div>
</div>
@endif

@if($showCreateModal)
<div class="modal-overlay" wire:click.self="closeCreateModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-create">
    <div class="modal-box max-w-6xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-create"><i class="fas fa-users mr-2"></i>Gestion des patients</h2>
            <button type="button" wire:click="closeCreateModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:patient-manager />
        </div>
    </div>
</div>
@endif

{{-- ── GESTION DU CABINET ── --}}

{{-- Caisse Paie --}}
@if($showCaisseOperations)
<div class="modal-overlay" wire:click.self="fermerCaisseOperationsModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-caisse">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-caisse"><i class="fas fa-cash-register mr-2"></i>Caisse Paie</h2>
            <button type="button" wire:click="fermerCaisseOperationsModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:caisse-operations-manager wire:key="caisse-operations-modal" />
        </div>
    </div>
</div>
@endif

{{-- Dépenses --}}
@if($showDepenses)
<div class="modal-overlay" wire:click.self="fermerDepensesModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-depenses">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-depenses"><i class="fas fa-receipt mr-2"></i>Dépenses</h2>
            <button type="button" wire:click="fermerDepensesModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:depenses-manager wire:key="depenses-manager-modal" />
        </div>
    </div>
</div>
@endif

{{-- Statistiques --}}
@if($showStatistiques)
<div class="modal-overlay" wire:click.self="fermerStatistiquesModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-statistiques">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-statistiques"><i class="fas fa-chart-bar mr-2"></i>Statistiques</h2>
            <button type="button" wire:click="fermerStatistiquesModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:statistiques-manager wire:key="statistiques-manager-modal" />
        </div>
    </div>
</div>
@endif

{{-- Assurances --}}
@if($showAssureurModal)
<div class="modal-overlay" wire:click.self="fermerAssureurModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-assureur">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-assureur"><i class="fas fa-shield-alt mr-2"></i>Gestion des assurances</h2>
            <button type="button" wire:click="fermerAssureurModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:assureur-manager wire:key="assureur-manager-modal" />
        </div>
    </div>
</div>
@endif

{{-- Actes / Soins --}}
@if($showListeActesModal)
<div class="modal-overlay" wire:click.self="fermerListeActesModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-liste-actes">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-liste-actes"><i class="fas fa-list-alt mr-2"></i>Liste des actes</h2>
            <button type="button" wire:click="fermerListeActesModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:acte-manager wire:key="acte-manager-modal" />
        </div>
    </div>
</div>
@endif

{{-- Médicaments --}}
@if($showListeMedicamentsModal)
<div class="modal-overlay" wire:click.self="fermerListeMedicamentsModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-liste-medicaments">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-liste-medicaments"><i class="fas fa-pills mr-2"></i>Liste des médicaments</h2>
            <button type="button" wire:click="fermerListeMedicamentsModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:medicament-manager wire:key="medicament-manager-modal" />
        </div>
    </div>
</div>
@endif

{{-- Médecins --}}
@if($showMedecinsModal)
<div class="modal-overlay" wire:click.self="fermerMedecinsModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-medecins">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-medecins"><i class="fas fa-user-md mr-2"></i>Gestion des médecins</h2>
            <button type="button" wire:click="fermerMedecinsModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:medecin-manager wire:key="medecin-manager-modal" />
        </div>
    </div>
</div>
@endif

{{-- Modes de paiement --}}
@if($showTypePaiementModal)
<div class="modal-overlay" wire:click.self="fermerTypePaiementModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-type-paiement">
    <div class="modal-box max-w-4xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-type-paiement"><i class="fas fa-credit-card mr-2"></i>Modes de paiement</h2>
            <button type="button" wire:click="fermerTypePaiementModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:type-paiement-manager wire:key="type-paiement-manager-modal" />
        </div>
    </div>
</div>
@endif

{{-- Suivi de stock --}}
@if($showDashboardStock)
<div class="modal-overlay" wire:click.self="fermerDashboardStockModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-dashboard-stock">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-dashboard-stock"><i class="fas fa-chart-line mr-2"></i>Suivi de stock</h2>
            <button type="button" wire:click="fermerDashboardStockModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:pharmacie-manager wire:key="dashboard-stock-modal" />
        </div>
    </div>
</div>
@endif

{{-- Salle d'attente --}}
@if($showSalleAttenteModal)
<div class="modal-overlay" wire:click.self="fermerSalleAttenteModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-salle-attente">
    <div class="modal-box max-w-4xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-salle-attente"><i class="fas fa-couch mr-2"></i>Salle d'attente</h2>
                <p class="text-sm text-white/70">Rendez-vous du jour — cliquez sur un patient pour accéder à son dossier</p>
            </div>
            <button type="button" wire:click="fermerSalleAttenteModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:salle-attente wire:key="salle-attente-modal" />
        </div>
    </div>
</div>
@endif

{{-- Salle de soins --}}
@if($showSalleSoinsModal)
<div class="modal-overlay" wire:click.self="fermerSalleSoinsModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-salle-soins">
    <div class="modal-box max-w-3xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-salle-soins"><i class="fas fa-syringe mr-2"></i>Salle de soins</h2>
                <p>Ordonnances internes prescrites aujourd'hui — soins à effectuer</p>
            </div>
            <button type="button" wire:click="fermerSalleSoinsModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:salle-soins wire:key="salle-soins-modal" />
        </div>
    </div>
</div>
@endif

{{-- Actes à effectuer --}}
@if($showActesPatientModal && $selectedPatient)
<div class="modal-overlay" wire:click.self="fermerActesPatientModal" role="dialog" aria-modal="true" aria-labelledby="modal-title-actes-patient">
    <div class="modal-box max-w-4xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-actes-patient"><i class="fas fa-procedures mr-2"></i>Actes à effectuer</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" wire:click="fermerActesPatientModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:actes-patient wire:key="actes-patient-{{ $patientId }}" :patient="$selectedPatient" />
        </div>
    </div>
</div>
@endif

{{-- Paramètres cabinet --}}
@if($showParametresCabinetModal)
<div class="modal-overlay" wire:click.self="fermerParametresCabinet" role="dialog" aria-modal="true" aria-labelledby="modal-title-parametres-cabinet">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <div>
                <h2 id="modal-title-parametres-cabinet"><i class="fas fa-sliders-h mr-2"></i>Paramètres du cabinet</h2>
                <p>Configuration de l'en-tête et du pied de page des imprimés</p>
            </div>
            <button type="button" wire:click="fermerParametresCabinet" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:parametres-cabinet wire:key="parametres-cabinet-modal" />
        </div>
    </div>
</div>
@endif

{{-- Utilisateurs: page dédiée /users --}}

{{-- Conteneur de notifications flottantes --}}
<div id="salle-notif-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none" style="max-width:320px;"></div>

<script>
/* ── Compteur générique badge ── */
function rafraichirCompteur(url, badgeId, countId) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById(badgeId);
            const count = document.getElementById(countId);
            if (!badge || !count) return;
            const n = data.count || 0;
            count.textContent = n;
            if (n > 0) {
                badge.classList.remove('hidden');
                badge.classList.add('inline-flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('inline-flex');
            }
        }).catch(() => {});
}

function rafraichirCompteurSalleAttente() {
    rafraichirCompteur('/api/salle-attente/count', 'salle-attente-badge', 'salle-attente-count');
}
function rafraichirCompteurSalleSoins() {
    rafraichirCompteur('/api/salle-soins/count', 'salle-soins-badge', 'salle-soins-count');
}
function rafraichirStockAlerts() {
    fetch('/api/stock/alerts', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const bFaible  = document.getElementById('stock-faible-badge');
            const cFaible  = document.getElementById('stock-faible-count');
            const bEpuise  = document.getElementById('stock-epuise-badge');
            const cEpuise  = document.getElementById('stock-epuise-count');
            if (bFaible && cFaible) {
                if (data.faible > 0) {
                    cFaible.textContent = data.faible;
                    bFaible.classList.remove('hidden');
                    bFaible.classList.add('inline-flex');
                } else {
                    bFaible.classList.add('hidden');
                    bFaible.classList.remove('inline-flex');
                }
            }
            if (bEpuise && cEpuise) {
                if (data.epuise > 0) {
                    cEpuise.textContent = data.epuise;
                    bEpuise.classList.remove('hidden');
                    bEpuise.classList.add('inline-flex');
                } else {
                    bEpuise.classList.add('hidden');
                    bEpuise.classList.remove('inline-flex');
                }
            }
        }).catch(() => {});
}

/* ── Afficher une notification flottante ── */
function afficherNotifSalle(icone, titre, message, couleur) {
    const container = document.getElementById('salle-notif-container');
    if (!container) return;

    const notif = document.createElement('div');
    notif.className = 'pointer-events-auto flex items-start gap-3 bg-white border-l-4 rounded-xl shadow-xl px-4 py-3 transition-all duration-500 opacity-0 translate-x-8';
    notif.style.borderColor = couleur;
    notif.innerHTML = `
        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5" style="background:${couleur}20">
            <i class="fas ${icone} text-sm" style="color:${couleur}"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">${titre}</p>
            <p class="text-sm font-bold text-gray-800 truncate mt-0.5">${message}</p>
        </div>
    `;
    container.appendChild(notif);

    // Entrée animée
    requestAnimationFrame(() => {
        notif.classList.remove('opacity-0', 'translate-x-8');
        notif.classList.add('opacity-100', 'translate-x-0');
    });

    // Sortie après 5 secondes
    setTimeout(() => {
        notif.classList.remove('opacity-100', 'translate-x-0');
        notif.classList.add('opacity-0', 'translate-x-8');
        setTimeout(() => notif.remove(), 500);
    }, 5000);
}

/* ── Notifications : enregistrées immédiatement, pas dans livewire:load ── */
// Éviter les doublons si Livewire re-rend le composant
if (!window._sysmedNotifInit) {
    window._sysmedNotifInit = true;

    window.addEventListener('nouveau-rdv-notif', e => {
        const d = e.detail;
        const msg = d.heure ? `${d.nom} — ${d.heure}` : d.nom;
        afficherNotifSalle('fa-calendar-check', 'Nouveau rendez-vous', msg, '#2563eb');
        rafraichirCompteurSalleAttente();
    });

    window.addEventListener('nouvelle-consultation-notif', e => {
        afficherNotifSalle('fa-stethoscope', 'Consultation enregistrée', e.detail.nom, '#16a34a');
        rafraichirCompteurSalleAttente();
    });

    window.addEventListener('nouvelle-ordonnance-interne-notif', e => {
        const d = e.detail;
        afficherNotifSalle('fa-syringe', 'Soin à effectuer', d.nom, 'var(--color-primary)');
        rafraichirCompteurSalleSoins();
    });
}

/* ── Compteurs polling ── */
if (!window._sysmedPollingInit) {
    window._sysmedPollingInit = true;
    rafraichirCompteurSalleAttente();
    rafraichirCompteurSalleSoins();
    rafraichirStockAlerts();
    setInterval(rafraichirCompteurSalleAttente, 15000);
    setInterval(rafraichirCompteurSalleSoins, 15000);
    setInterval(rafraichirStockAlerts, 60000);
}

document.addEventListener('livewire:load', function () {

    // Active state for patient sub-menu buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.patient-nav-button');
        if (btn) {
            document.querySelectorAll('.patient-nav-button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    });

    // Modal loading indicator
    window.addEventListener('modal-loading', function(e) {
        const loadingDiv = document.getElementById('modal-loading');
        if (!loadingDiv) return;
        if (e.detail.loading) {
            loadingDiv.classList.remove('hidden');
        } else {
            loadingDiv.classList.add('hidden');
        }
    });

    // Keyboard shortcuts (when modal is open)
    document.addEventListener('keydown', function(e) {
        if (!document.querySelector('.modal-overlay')) return;
        if (e.ctrlKey && e.key === 'Enter') {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.click();
        }
    });
});

/* ── Accessibilité modals : focus trap + restauration du focus ── */
(function () {
    let lastFocusedElement = null;

    function getFocusableElements(container) {
        return Array.from(container.querySelectorAll(
            'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
        )).filter(el => el.offsetParent !== null);
    }

    function trapTabKey(e, modalBox) {
        if (e.key !== 'Tab') return;
        const focusable = getFocusableElements(modalBox);
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function handleModalOpen(overlay) {
        lastFocusedElement = document.activeElement;
        const box = overlay.querySelector('.modal-box');
        if (box) {
            box.focus();
            document.addEventListener('keydown', function trapHandler(e) {
                if (!document.body.contains(overlay)) {
                    document.removeEventListener('keydown', trapHandler);
                    return;
                }
                trapTabKey(e, box);
            });
        }
    }

    function handleModalClose() {
        if (lastFocusedElement) {
            lastFocusedElement.focus();
            lastFocusedElement = null;
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const overlay = document.querySelector('.modal-overlay');
            if (overlay) {
                const closeBtn = overlay.querySelector('.modal-close');
                if (closeBtn) closeBtn.click();
            }
        }
    });

    const modalObserver = new MutationObserver(function (mutations) {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType === 1 && node.classList && node.classList.contains('modal-overlay')) {
                    handleModalOpen(node);
                }
            });
            mutation.removedNodes.forEach(function (node) {
                if (node.nodeType === 1 && node.classList && node.classList.contains('modal-overlay')) {
                    handleModalClose();
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('[wire\\:id]') || document.body;
        modalObserver.observe(root, { childList: true, subtree: true });
    });
})();
</script>

</div>{{-- fin div racine --}}
