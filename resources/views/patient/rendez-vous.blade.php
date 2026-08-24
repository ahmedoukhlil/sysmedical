<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع طابور الانتظار - {{ $patient->Nom }}</title>
    <link rel="stylesheet" href="/css/app.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .status-en-attente { @apply bg-yellow-100 text-yellow-800; }
        .status-confirme { @apply bg-green-100 text-green-800; }
        .status-annule { @apply bg-red-100 text-red-800; }
        .status-termine { @apply bg-blue-100 text-blue-800; }
        .current-patient { @apply bg-blue-500 text-white; }
        .other-patient { @apply bg-gray-200 text-gray-700; }
        
        /* RTL specific styles */
        .rtl-text { direction: rtl; text-align: right; }
        .icon-left { margin-left: 0.5rem; margin-right: 0; }
        .icon-right { margin-right: 0.5rem; margin-left: 0; }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
                 <!-- En-tête du cabinet -->
         <div class="bg-white rounded-lg shadow-md p-6 mb-6">
             @include('partials.recu-header')
         </div>
 
                  <!-- Informations principales -->
         <div class="bg-white rounded-lg shadow-md p-6 mb-6">
             <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                 <!-- معلومات المريض -->
                 <div>
                     <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user text-blue-600 icon-left"></i>
                        معلومات المريض
                     </h2>
                     <div class="space-y-3">
                         <div>
                            <span class="text-sm font-medium text-gray-600">الاسم:</span>
                             <span class="text-lg font-semibold text-gray-800">{{ $patient->Nom }}</span>
                         </div>
                         @if($prochainRdv)
                             <div>
                                <span class="text-sm font-medium text-gray-600">الطبيب:</span>
                                <span class="text-lg font-semibold text-gray-800">د. {{ $prochainRdv->medecin->Nom ?? '' }} {{ $prochainRdv->medecin->Prenom ?? '' }}</span>
                             </div>
                             <div>
                                <span class="text-sm font-medium text-gray-600">التاريخ:</span>
                                 <span class="text-lg font-semibold text-gray-800">{{ \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') }}</span>
                             </div>
                         @endif
                     </div>
                 </div>
 
                                                 <!-- الحالة الحالية -->
                 <div>
                     <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-clock text-green-600 icon-left"></i>
                        الحالة الحالية
                     </h2>
                    @if($prochainRdv)
                        @if($estAujourdhui)
                            <!-- موعد اليوم - عرض طابور الانتظار -->
                         <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-4 text-white">
                             <div class="text-center">
                                    <div class="text-3xl font-bold mb-2">{{ $prochainRdv->OrdreRDV ?? $positionPatient }}</div>
                                    <div class="text-sm opacity-90">رقم موعدك</div>
                                    
                                    @if($prochainRdv->rdvConfirmer == 'En cours')
                                        <!-- المريض مع الطبيب -->
                                        <div class="bg-green-500 bg-opacity-30 rounded-lg p-3 mt-3">
                                            <div class="text-lg font-bold text-green-200">
                                                <i class="fas fa-user-md icon-left"></i>
                                                انتم الآن مع الطبيب
                                            </div>
                                            <div class="text-sm opacity-90">قيد العلاج</div>
                                        </div>
                                    @elseif($patientsAvantMoi > 0)
                                        <div class="bg-white bg-opacity-20 rounded-lg p-3 mt-3">
                                            <div class="text-2xl font-bold text-yellow-200">{{ $patientsAvantMoi }}</div>
                                            <div class="text-sm opacity-90">
                                                <i class="fas fa-users icon-left"></i>
                                                مريض قبلك
                                            </div>
                                        </div>
                                    @else
                                        <div class="bg-green-500 bg-opacity-30 rounded-lg p-3 mt-3">
                                            <div class="text-lg font-bold text-green-200">
                                                <i class="fas fa-star icon-left"></i>
                                                حان دورك !
                                            </div>
                                            <div class="text-sm opacity-90">يمكنك الحضور</div>
                                     </div>
                                 @endif
                                    
                                    @if($tempsAttenteEstime > 0)
                                        <div class="text-sm opacity-90 mt-2">
                                            <i class="fas fa-hourglass-half icon-left"></i>
                                            وقت الانتظار المقدر: ~{{ $tempsAttenteEstime }} دقيقة
                                        </div>
                                    @else
                                     <div class="text-sm opacity-90 mt-2">
                                            <i class="fas fa-clock icon-left"></i>
                                            لا انتظار متوقع
                                     </div>
                                 @endif
                             </div>
                         </div>
                                                 @elseif($estFutur)
                             <!-- موعد في المستقبل -->
                             <div class="bg-orange-100 border border-orange-300 rounded-lg p-4">
                                 <div class="text-center">
                                     <i class="fas fa-calendar-alt text-orange-600 text-3xl mb-3"></i>
                                     <div class="text-orange-800 font-bold text-lg mb-2">انتظر يوم موعدك</div>
                                     <div class="text-orange-700 text-sm mb-3">
                                         موعدك مقرر يوم: <strong>{{ \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') }}</strong>
                                     </div>
                                     <div class="text-orange-600 text-xs">
                                         <i class="fas fa-info-circle icon-left"></i>
                                         ستتمكن من متابعة طابور الانتظار في يوم موعدك فقط
                                     </div>
                                     <hr class="my-2 border-orange-300">
                                     <div class="text-orange-800 font-bold text-lg">Attendre le jour de votre RDV</div>
                                     <div class="text-orange-700 text-sm">
                                         Votre rendez-vous est prévu le: <strong>{{ \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') }}</strong>
                                     </div>
                                     <div class="text-orange-600 text-xs">
                                         <i class="fas fa-info-circle icon-left"></i>
                                         Vous pourrez suivre la file d'attente le jour de votre RDV uniquement
                                     </div>
                                 </div>
                             </div>
                         @elseif($estPasse)
                             <!-- موعد منتهي -->
                             <div class="bg-red-100 border border-red-300 rounded-lg p-4">
                                 <div class="text-center">
                                     <i class="fas fa-calendar-times text-red-600 text-3xl mb-3"></i>
                                     <div class="text-red-800 font-bold text-lg mb-2">موعدك منتهي</div>
                                     <div class="text-red-700 text-sm mb-3">
                                         موعدك كان يوم: <strong>{{ \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') }}</strong>
                                     </div>
                                     <div class="text-red-600 text-xs">
                                         <i class="fas fa-exclamation-triangle icon-left"></i>
                                         الرابط منتهي الصلاحية
                                     </div>
                                     <hr class="my-2 border-red-300">
                                     <div class="text-red-800 font-bold text-lg">Votre RDV a dépassé</div>
                                     <div class="text-red-700 text-sm">
                                         Votre rendez-vous était le: <strong>{{ \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') }}</strong>
                                     </div>
                                     <div class="text-red-600 text-xs">
                                         <i class="fas fa-exclamation-triangle icon-left"></i>
                                         Le lien est expiré
                                     </div>
                                 </div>
                             </div>
                         @endif
                     @else
                         <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4">
                             <div class="text-center">
                                 <i class="fas fa-info-circle text-yellow-600 text-2xl mb-2"></i>
                                <div class="text-yellow-800 font-medium">لا توجد مواعيد مقررة</div>
                             </div>
                         </div>
                     @endif
                 </div>
 
                                 <!-- المريض الحالي -->
                @if($prochainRdv && $patientEnCours && $estAujourdhui)
                     <div>
                         <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-user-md text-green-600 icon-left"></i>
                            المريض الحالي
                         </h2>
                         <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                             <div class="text-center">
                                 <div class="text-3xl font-bold mb-2">
                                    <i class="fas fa-user-md icon-left"></i>
                                    رقم {{ $patientEnCours->OrdreRDV ?? $positionPatientEnCours ?? 'غير متوفر' }}
                                 </div>
                                <div class="text-sm opacity-90">مع الطبيب</div>
                                <div class="text-xs opacity-75 mt-1">قيد العلاج</div>
                             </div>
                         </div>
                     </div>
                 @endif
             </div>
         </div>

        <!-- المريض قيد العلاج -->
        @if($prochainRdv && $patientEnCours && $estAujourdhui)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-green-800">
                            <i class="fas fa-user-md icon-left"></i>
                            المريض قيد العلاج
                        </h3>
                        <p class="text-green-700">الرقم: <span class="font-bold">{{ $patientEnCours->OrdreRDV ?? $positionPatientEnCours ?? 'غير متوفر' }}</span></p>
                    </div>
                    <div class="text-left">
                                                 <div class="text-2xl font-bold text-green-600">
                             <i class="fas fa-user-md icon-left"></i>
                             مع الطبيب
                         </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- المريض الحالي مع الطبيب -->
        @if($prochainRdv && $prochainRdv->rdvConfirmer == 'En cours' && $estAujourdhui)
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 mb-6 text-white text-center">
                <div class="text-4xl mb-4">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="text-2xl font-bold mb-2">انتم الآن مع الطبيب</div>
                <div class="text-lg opacity-90">قيد العلاج - يرجى الانتظار</div>
                <div class="text-sm opacity-75 mt-2">
                    <i class="fas fa-clock icon-left"></i>
                    رقم موعدك: {{ $prochainRdv->OrdreRDV }}
                </div>
            </div>
        @endif

        <!-- برنامج الطبيب -->
        <livewire:patient-queue-status :token="$token" wire:key="patient-queue-{{ $patient->ID }}" />



        <!-- تذييل الصفحة -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>تُحدث هذه الصفحة تلقائياً كل 30 ثانية</p>
            <p class="mt-2">
                <i class="fas fa-shield-alt icon-left"></i>
                بياناتك محمية وسرية
            </p>
        </div>
    </div>

    @livewireScripts
</body>
</html> 