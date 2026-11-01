@extends('layouts.admin')

@section('title', 'Add student')
@section('eyebrow', 'Student accounts')
@section('page-title', 'Add a student')

@section('content')
    <div class="mb-6"><a href="{{ route('admin.students.index') }}" class="text-sm font-bold text-slate-600 hover:text-slate-950">← Back to student accounts</a></div>
    @include('admin.students._form', ['student' => $student])
@endsection
