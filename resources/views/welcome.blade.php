<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waspito Rewards</title>
    <!-- 👈 Inclure app.css aux côtés de app.js -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div id="app">
        <!-- Composant Vue.js -->
        <user-list></user-list>
    </div>
</body>
</html>