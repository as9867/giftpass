@extends('backend.layouts.app')

@section('title', 'Brands')

@section('breadcrumb-links')
    {{-- @include('backend.auth.user.includes.breadcrumb-links') --}}
@endsection

@section('content')
    <x-backend.card>
        <x-slot name="header">
            Category
        </x-slot>
        <x-slot name="body">
            <livewire:backend.category-table />
        </x-slot>
    </x-backend.card>
@endsection
 