<?php

namespace App\Console\Commands;

use App\Services\Auth\LdapService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ldap:sync {--test : Test la connexion sans synchroniser} {--force : Force la synchronisation}')]
#[Description('Synchronise les utilisateurs depuis Active Directory')]
class SyncLdapUsers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(LdapService $ldapService)
    {
        if ($this->option('test')) {
            return $this->testConnection($ldapService);
        }

        if (!$ldapService->isEnabled() && !$this->option('force')) {
            $this->error('❌ LDAP n\'est pas activé dans la configuration');
            $this->info('💡 Activez LDAP_ENABLED=true dans votre fichier .env');
            $this->info('💡 Ou utilisez --force pour tester la configuration');
            return self::FAILURE;
        }

        $this->info('🔄 Synchronisation des utilisateurs depuis Active Directory...');
        
        try {
            $count = $ldapService->syncUsers();
            
            if ($count > 0) {
                $this->info("✅ {$count} utilisateur(s) synchronisé(s) avec succès!");
                return self::SUCCESS;
            } else {
                $this->warn('⚠️  Aucun utilisateur synchronisé');
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la synchronisation: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function testConnection(LdapService $ldapService): int
    {
        $this->info('🔍 Test de connexion à Active Directory...');
        
        if (!extension_loaded('ldap')) {
            $this->error('❌ L\'extension PHP LDAP n\'est pas installée');
            $this->info('💡 Pour activer LDAP:');
            $this->info('   1. Ouvrez php.ini: ' . php_ini_loaded_file());
            $this->info('   2. Décommentez: extension=ldap');
            $this->info('   3. Redémarrez le serveur web');
            return self::FAILURE;
        }

        $this->info('✅ Extension PHP LDAP détectée');
        
        $this->info('📋 Configuration actuelle:');
        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['LDAP Activé', config('ldap.enabled') ? '✅ Oui' : '❌ Non'],
                ['Serveur', config('ldap.host')],
                ['Port', config('ldap.port')],
                ['Base DN', config('ldap.base_dn')],
                ['Auto-sync', config('ldap.auto_sync') ? '✅ Oui' : '❌ Non'],
            ]
        );

        return self::SUCCESS;
    }
}
