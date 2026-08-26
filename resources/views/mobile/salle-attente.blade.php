@extends('layouts.mobile')

@section('content')
    @livewire('salle-attente', ['modeMobile' => true])
@endsection
