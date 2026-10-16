<x-intranet-layout title="Départements">
    
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-graphite-900 mb-2">
            Départements
        </h1>
        <p class="text-graphite-600">
            Découvrez tous les départements de NÉRÉ Mining et leurs services
        </p>
    </div>

    <!-- Departments Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        @foreach(\App\Models\Department::active()->withCount('services')->ordered()->get() as $department)
            <a 
                href="{{ route('intranet.departments.show', strtolower($department->code)) }}"
                class="bg-white rounded-xl border-2 border-graphite-200 hover:border-nere-500 hover:shadow-lg transition-all duration-300 overflow-hidden group"
            >
                <!-- Department Image -->
                <div class="relative h-40 bg-gradient-to-br from-graphite-50 to-graphite-100 overflow-hidden">
                    @if($department->image_url)
                        <img 
                            src="{{ $department->image_url }}" 
                            alt="{{ $department->name }}"
                            loading="lazy" decoding="async"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        >
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-graphite-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif

                    <!-- Tag -->
                    <div class="absolute top-3 left-3">
                        <span class="px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full text-xs font-bold text-graphite-700 uppercase tracking-wider">
                            {{ $department->tag ?? $department->code }}
                        </span>
                    </div>
                </div>

                <!-- Department Info -->
                <div class="p-5">
                    <h3 class="text-xl font-bold text-graphite-900 mb-2 group-hover:text-nere-600 transition">
                        {{ $department->name }}
                    </h3>
                    <p class="text-sm text-graphite-600 line-clamp-2 mb-4">
                        {{ $department->description }}
                    </p>

                    <!-- Services Count -->
                    <div class="flex items-center justify-between pt-4 border-t border-graphite-100">
                        <span class="text-xs text-graphite-500">
                            {{ $department->services_count }} service(s)
                        </span>
                        <svg class="w-5 h-5 text-nere-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
        @endforeach

    </div>

</x-intranet-layout>
