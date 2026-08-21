@extends('layouts.admin')

@section('content')
    <div class="flex flex-col grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 m-5">         
        @include('ikode.manager', ['scope' => 'admin'])
    </div>
@endsection
