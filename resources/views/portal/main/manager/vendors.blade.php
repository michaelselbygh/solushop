@extends('portal.layouts.manager.master')

@section('page-title')Vendors @endsection

@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/vendor/list.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <vendors></vendors>
    </div>
@endsection