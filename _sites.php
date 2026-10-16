<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (Illuminate\Support\Facades\DB::table('sites')->orderBy('nom')->get(['id','nom','code','actif']) as $s) echo $s->id.'|'.$s->code.'|'.$s->nom.'|'.($s->actif?'actif':'inactif').PHP_EOL;
