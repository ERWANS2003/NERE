@extends('admin.layout')

@section('title', 'Matrice des droits')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><h2 class="text-2xl font-bold">Rôles et permissions</h2><p class="mt-1 text-sm text-gray-600">Comparez les droits directs de chaque rôle et repérez les autorisations sensibles.</p></div>
    <a href="{{ route('admin.roles.index') }}" class="bouton-secondaire"><x-icone nom="arrow-left" />Liste des rôles</a>
</div>

<div class="mb-4 flex flex-wrap gap-4 text-xs text-gray-600"><span><span class="inline-block h-3 w-3 rounded-full bg-green-600"></span> Permission directe</span><span><span class="inline-block h-3 w-3 rounded-full bg-red-600"></span> Permission sensible</span><span>Les super-administrateurs héritent implicitement de tous les droits, indépendamment des rôles.</span></div>

<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
<table class="min-w-max text-sm">
    <thead><tr><th class="sticky left-0 z-10 min-w-72 bg-gray-50 px-4 py-3 text-left">Permission</th>@foreach($roles as $role)<th class="min-w-36 px-3 py-3 text-center"><a href="{{ route('admin.roles.show', $role) }}" class="hover:text-nere-700">{{ $role->nom }}</a><span class="block text-xs font-normal text-gray-500">{{ $role->users_count }} utilisateur(s)</span></th>@endforeach</tr></thead>
    <tbody class="divide-y divide-gray-100">
    @foreach($permissionGroups as $group => $permissions)
        <tr><th colspan="{{ $roles->count() + 1 }}" class="bg-gray-100 px-4 py-2 text-left text-xs uppercase tracking-wide text-gray-600">{{ $group }}</th></tr>
        @foreach($permissions as $permission)
        @php($sensitive = in_array($permission->slug, $sensitivePermissions, true))
        <tr>
            <td class="sticky left-0 z-10 bg-white px-4 py-3"><span class="font-medium">{{ $permission->nom }}</span>@if($sensitive)<span class="ml-2 rounded bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase text-red-800">Sensible</span>@endif<span class="block text-xs text-gray-500">{{ $permission->slug }}</span></td>
            @foreach($roles as $role)
                @php($granted = $role->permissions->contains('id', $permission->id))
                <td class="px-3 py-3 text-center"><span class="inline-grid h-7 w-7 place-items-center rounded-full {{ $granted ? ($sensitive ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700') : 'bg-gray-50 text-gray-300' }}" aria-label="{{ $granted ? 'Accordée' : 'Non accordée' }}">{{ $granted ? '✓' : '—' }}</span></td>
            @endforeach
        </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
</div>
@endsection
