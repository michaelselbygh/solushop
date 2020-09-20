@extends('portal.layouts.manager.master')

@section('page-title')Orders @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/dashboard.js') }}" defer></script>
@endsection
@section('content-body')
    <div id="page">
        <dashboard></dashboard>
    </div>
@endsection