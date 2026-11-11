<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {recipient}';
    protected $description = 'Send a test email to verify SMTP configuration';

    public function handle()
    {
        $recipient = $this->argument('recipient');
        
        try {
            Mail::raw('Ceci est un email de test depuis l\'Intranet NÉRÉ Mining. Si vous recevez ce message, la configuration SMTP fonctionne correctement.', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Test SMTP - Intranet NERE Mining');
            });
            
            $this->info("✓ Email envoyé avec succès à {$recipient}");
            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Erreur lors de l'envoi: " . $e->getMessage());
            return 1;
        }
    }
}
