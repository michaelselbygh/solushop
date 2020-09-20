@extends('portal.layouts.manager.master')

@section('page-title')Sales Associates @endsection

@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/sales-associate/list.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <associates></associates>
    </div>
@endsection