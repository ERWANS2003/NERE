<?php

/**
 * Vercel Serverless Function pour Laravel ITSM
 * Configuration simplifiée sans dépendances Composer
 */

// Configuration de base
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Vérifier si Laravel est disponible
$vendor_path = __DIR__ . '/../vendor/autoload.php';
$bootstrap_path = __DIR__ . '/../bootstrap/app.php';

if (!file_exists($vendor_path) || !file_exists($bootstrap_path)) {
    // Si Laravel n'est pas disponible, afficher une page temporaire
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ITSM NERE Mining - En cours de déploiement</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .container { text-align: center; }
            .status { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; text-align: left; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚀 ITSM NERE Mining</h1>
            <div class="status">
                <h2>✅ Déploiement Vercel Réussi !</h2>
                <p>Votre application Laravel ITSM est en cours de configuration...</p>
            </div>
            
            <div class="info">
                <h3>📋 Étapes de configuration :</h3>
                <ul>
                    <li>✅ Déploiement sur Vercel réussi</li>
                    <li>🔄 Configuration des variables d'environnement</li>
                    <li>🔄 Configuration de la base de données</li>
                    <li>⏳ Installation des dépendances Laravel</li>
                </ul>
            </div>
            
            <div class="info">
                <h3>🛠️ Prochaines étapes :</h3>
                <ol>
                    <li><strong>Configurer les variables d'environnement</strong> dans Vercel</li>
                    <li><strong>Créer la base de données Neon</strong></li>
                    <li><strong>Installer Composer</strong> via un runtime différent</li>
                </ol>
            </div>
            
            <div class="info">
                <p><strong>État actuel :</strong> <?php echo date('Y-m-d H:i:s T'); ?></p>
                <p><strong>Version PHP :</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>Extensions disponibles :</strong> 
                <?php 
                $extensions = ['pdo', 'pdo_pgsql', 'openssl', 'mbstring', 'json'];
                $available = array_filter($extensions, 'extension_loaded');
                echo implode(', ', $available);
                ?>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Si Laravel est disponible, l'exécuter
try {
    require $vendor_path;
    $app = require $bootstrap_path;
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::capture();
    
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo "Application Error: " . $e->getMessage();
}