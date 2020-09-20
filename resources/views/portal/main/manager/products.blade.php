@extends('portal.layouts.manager.master')

@section('page-title')Products @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/product/dashboard.js') }}" defer></script>
@endsection
@section('content-body')
    <div id="page">
        <dashboard></dashboard>
    </div>
@endsection

