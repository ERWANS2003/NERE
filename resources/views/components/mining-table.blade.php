@props(['striped' => true, 'hoverable' => true])

@php
$stripedClass = $striped ? 'odd:bg-charcoal-900/30' : '';
$hoverClass = $hoverable ? 'hover:bg-charcoal-800/50 transition-colors' : '';
@endphp

<div class="mining-card overflow-hidden p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-gold-900/30 to-crimson-900/30 border-b border-gold-500/30">
                <tr>
                    {{ $slot }}
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal-700/50">
                {{ $body ?? '' }}
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        // Table row animation on load
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.animation = `fadeInUp 0.3s ease-out ${index * 0.05}s forwards`;
            row.style.opacity = '0';
        });
    });
</script>
@endpush

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
