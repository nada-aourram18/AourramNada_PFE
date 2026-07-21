@extends('layouts.app')

@section('title', __('messages.edit'))
@section('heading', __('messages.edit'))
@section('subheading', $patient->full_name)

@section('content')
<form method="post" action="{{ route('patients.update',$patient) }}" class="max-w-3xl space-y-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
    @csrf @method('PUT')
    @include('patients._form', ['patient' => $patient])
    <div class="flex flex-wrap gap-2 pt-2">
        <button class="rounded-xl bg-medical px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-medical/20 transition-all duration-200 hover:bg-medical-dark active:scale-[0.98]" type="submit">{{ __('messages.save') }}</button>
        <a href="{{ route('patients.show',$patient) }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">{{ __('messages.cancel') }}</a>
    </div>
</form>
@endsection
