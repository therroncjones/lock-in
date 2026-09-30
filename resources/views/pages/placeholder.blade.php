@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-3 px-4 py-5 md:px-8 md:py-8">
        <h1 class="text-3xl font-bold tracking-tight">{{ $heading }}</h1>
        <p class="text-neutral-500">{{ $message }}</p>
    </div>
@endsection
