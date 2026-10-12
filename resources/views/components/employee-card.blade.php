@props(['employee'])

<div class="bg-white rounded-lg border border-graphite-200 p-6 hover:shadow-md hover:border-nere-300 transition text-center">

    {{-- Avatar --}}
    <x-avatar :user="$employee" size="xl" class="mx-auto mb-4" />

    {{-- Nom --}}
    <h3 class="font-bold text-graphite-900 mb-1 truncate">
        {{ $employee->name }}
    </h3>

    {{-- Poste --}}
    @if($employee->job_title)
        <p class="text-sm text-graphite-600 mb-3 truncate">
            {{ $employee->job_title }}
        </p>
    @else
        <p class="text-sm text-graphite-400 mb-3 italic">Poste non renseigné</p>
    @endif

    {{-- Contact --}}
    <div class="space-y-2 mb-4">
        <a href="mailto:{{ $employee->email }}"
           class="flex items-center justify-center gap-2 text-sm text-graphite-600 hover:text-nere-600 transition">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="truncate">{{ $employee->email }}</span>
        </a>

        @if($employee->telephone)
            <a href="tel:{{ $employee->telephone }}"
               class="flex items-center justify-center gap-2 text-sm text-graphite-600 hover:text-nere-600 transition">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span>{{ $employee->telephone }}</span>
            </a>
        @endif
    </div>

    {{-- Badges départements --}}
    @if($employee->departments->count() > 0)
        <div class="flex flex-wrap gap-1 justify-center">
            @foreach($employee->departments as $dept)
                <span class="px-2 py-1 bg-graphite-100 text-graphite-600 rounded text-xs font-medium">
                    {{ $dept->code }}
                </span>
            @endforeach
        </div>
    @endif

</div>
