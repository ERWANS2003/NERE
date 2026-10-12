{{--
    Relaie les messages flash de session en notifications toast (toastr, chargé
    par app.js). Chaque clé de session correspond à un niveau toastr.

    Les messages sont encodés en JSON pour échapper proprement guillemets et
    retours à la ligne : aucun contenu de session n'est injecté brut dans le JS.
--}}
@php
    $toasts = collect([
        'success' => 'success',
        'error' => 'error',
        'warning' => 'warning',
        'status' => 'info',
        'info' => 'info',
    ])->filter(fn ($niveau, $cle) => session()->has($cle))
      ->map(fn ($niveau, $cle) => ['level' => $niveau, 'message' => (string) session($cle)])
      ->values();
@endphp

@if ($toasts->isNotEmpty())
    @push('scripts')
        <script>
            (function () {
                const toasts = @json($toasts);
                function afficher() {
                    if (!window.toastr) {
                        // app.js pas encore prêt : on retente au prochain tick.
                        return window.setTimeout(afficher, 50);
                    }
                    toasts.forEach(function (t) {
                        (window.toastr[t.level] || window.toastr.info)(t.message);
                    });
                }
                if (document.readyState !== 'loading') {
                    afficher();
                } else {
                    document.addEventListener('DOMContentLoaded', afficher, { once: true });
                }
            })();
        </script>
    @endpush
@endif
