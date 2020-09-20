@extends('portal.layouts.manager.master')

@section('page-title')Customers @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/customer/list.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <customers></customers>
    </div>
@endsection

