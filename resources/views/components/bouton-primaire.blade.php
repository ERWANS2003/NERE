<button {{ $attributes->merge(['type' => 'submit', 'class' => 'bouton-primaire']) }}>
    {{ $slot }}
</button>