<?php

beforeEach(function () {
    config(['app.locale' => 'fr']);
    app()->setLocale('fr');
});

it('shows error pages in French', function () {
    $this->get('/page-qui-n-existe-pas')
        ->assertNotFound()
        ->assertSee('Page introuvable');
});

it('names the login fields in French in validation messages', function () {
    $this->post(route('login'), ['identifiant' => '', 'password' => ''])
        ->assertSessionHasErrors([
            'identifiant' => 'Le champ identifiant est obligatoire.',
            'password' => 'Le champ mot de passe est obligatoire.',
        ]);
});
