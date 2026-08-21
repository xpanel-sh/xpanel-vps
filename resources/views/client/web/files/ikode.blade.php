@extends('layouts.client')

@section('content')
    <div class="flex flex-col grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 m-5 lg:mt-(--header-height)">
        @include('ikode.manager', ['scope' => 'client'])
    </div>
@endsection
