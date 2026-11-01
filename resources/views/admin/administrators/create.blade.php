@extends('layouts.admin')

@section('title', 'Add administrator')
@section('eyebrow', 'Access governance')
@section('page-title', 'Add administrator')

@section('content')
    @include('admin.administrators._form')
@endsection
