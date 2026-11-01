@extends('layouts.admin')

@section('title', 'Edit administrator')
@section('eyebrow', 'Access governance')
@section('page-title', 'Edit '.$administrator->name)

@section('content')
    @include('admin.administrators._form')
@endsection
