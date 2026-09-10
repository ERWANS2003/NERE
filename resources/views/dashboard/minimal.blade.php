@extends('layouts.portal-minimal')

@section('titre', 'Dashboard')
@section('sous-titre', 'Test')

@section('contenu')
<div class="p-6">
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h2 class="text-2xl font-bold text-white mb-4">Dashboard Minimal Test</h2>
        <p class="text-gray-400">If you see this, Blade rendering works!</p>
        <p class="text-gray-500 mt-2">Stats: {{ json_encode($stats ?? []) }}</p>
    </div>
</div>
@endsection
