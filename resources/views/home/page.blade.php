@extends('layouts.home')
@section('title', $content['title'].' — '.$home['company_name'])
@section('content')
<div class="bg-muted/40 border-b border-border"><div class="kt-container-fixed py-10 lg:py-12.5"><a href="{{ route('home') }}" class="kt-link"><i class="ki-filled ki-arrow-left"></i> Volver al inicio</a><h1 class="mt-5 text-3xl font-semibold text-mono">{{ $content['title'] }}</h1><p class="mt-2 text-sm text-secondary-foreground">Información publicada por {{ $home['company_name'] }}</p></div></div>
<div class="kt-container-fixed py-10"><article class="kt-card"><div class="kt-card-content p-7.5 lg:p-10 text-secondary-foreground leading-8 whitespace-pre-line">{{ $content['body'] }}</div></article></div>
@endsection
