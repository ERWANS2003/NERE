@extends('layouts.portal')

@section('contenu')
<div style="padding: 20px;">
    <h1>Portal Layout Test!</h1>
    <p>User: {{ auth()->user()->name }}</p>
</div>
@endsection
