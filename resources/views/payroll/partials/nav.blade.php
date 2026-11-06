<div class="mb-6 flex flex-col gap-4 border-b border-graphite-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-3xl font-bold text-graphite-900">{{ $title }}</h1>
        @isset($subtitle)<p class="mt-1 text-sm text-graphite-600">{{ $subtitle }}</p>@endisset
    </div>
    <nav aria-label="Navigation de la paie" class="flex flex-wrap gap-1 text-sm">
        @foreach([['payroll.index', ['payroll.index', 'payroll.periods.*'], 'Mois de paie'], ['payroll.profiles.index', ['payroll.profiles.*'], 'Salariés'], ['payroll.rubrics.index', ['payroll.rubrics.*'], 'Rubriques et paramètres']] as [$route, $patterns, $label])
            @php($active = request()->routeIs(...$patterns))
            <a href="{{ route($route) }}" @if($active) aria-current="page" @endif class="rounded-md px-3 py-2 font-medium {{ $active ? 'bg-nere-50 text-nere-800' : 'text-graphite-600 hover:bg-graphite-50' }}">{{ $label }}</a>
        @endforeach
    </nav>
</div>
@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif
