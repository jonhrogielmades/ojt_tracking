@extends('layouts.admin')

@section('title', 'Edit student')
@section('eyebrow', 'Student accounts')
@section('page-title', 'Edit '.$student->name)

@section('content')
    <div class="mb-6"><a href="{{ route('admin.students.show', $student) }}" class="text-sm font-bold text-slate-600 hover:text-slate-950">← Back to student details</a></div>
    @include('admin.students._form', ['student' => $student])
@endsection
