{{-- Must not depend on the DB, session or $store: it renders when something has already gone wrong. --}}
@extends('errors::minimal')

@section('title', 'Terjadi Kesalahan')
@section('code', '500')
@section('icon', 'exclamation-triangle')
@section('message', 'Terjadi kesalahan pada server')
@section('description', 'Maaf, ada gangguan saat memproses permintaan Anda. Silakan coba beberapa saat lagi.')
