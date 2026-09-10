@extends('layouts.portal-minimal')

@section('contenu')
<div style="padding: 20px;">
    <h1>Extends Test Works!</h1>
    <p>User: {{ auth()->user()->name }}</p>
</div>
@endsection
