@extends('layouts.app')

@section('title', 'Correos - XPanel')
@section('body_class', 'antialiased h-full text-base text-foreground bg-muted overflow-hidden')

@section('content')
    @include('xmail.index')
@endsection
