@extends('portal.layouts.manager.master')

@section('page-title')Delivery Partners @endsection

@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/delivery-partner/list.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <partners></partners>
    </div>
@endsection