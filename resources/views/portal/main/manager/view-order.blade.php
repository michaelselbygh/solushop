@extends('portal.layouts.manager.master')

@section('page-title')Order @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/order.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <order id="{{ $order["id"] }}"></order>
    </div>
@endsection
    
    

