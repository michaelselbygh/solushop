@extends('portal.layouts.manager.master')

@section('page-title')Add Sales Associate @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/sales-associate/add.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <associate></associate>
    </div>
@endsection


