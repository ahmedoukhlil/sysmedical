<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre rendez-vous</title>
    <link rel="stylesheet" href="/css/app.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6">
        <livewire:patient-otp-login :cabinet-token="$cabinetToken" />
    </div>
    @livewireScripts
</body>
</html>
