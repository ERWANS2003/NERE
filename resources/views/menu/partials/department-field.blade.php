@php
    $other = \App\Services\Menu\MenuDepartments::OTHER;
    $current = filled($value ?? null) ? \App\Services\Menu\MenuDepartments::canonical($value, $departments) : '';
    $known = $current === '' || $departments->contains($current);
    $selected = $known ? $current : $other;
@endphp
<div x-data="{ choix: @js($selected) }" class="{{ $class ?? '' }}">
    <select name="department" x-model="choix" required class="champ" aria-label="Département">
        <option value="" disabled @selected($selected === '')>Choisissez votre département</option>
        @foreach($departments as $name)
            <option value="{{ $name }}" @selected($selected === $name)>{{ $name }}</option>
        @endforeach
        <option value="{{ $other }}" @selected($selected === $other)>Autre (préciser)</option>
    </select>
    <input name="department_other" x-show="choix === @js($other)" x-cloak :required="choix === @js($other)" :disabled="choix !== @js($other)"
           value="{{ $known ? '' : $current }}" maxlength="150" placeholder="Nom du département, du site ou de l'entreprise" class="champ mt-2" aria-label="Autre département">
</div>
