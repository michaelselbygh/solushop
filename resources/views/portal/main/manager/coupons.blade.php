@extends('portal.layouts.manager.master')

@section('page-title')Coupons @endsection

@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/coupon/list.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <coupons></coupons>
    </div>
@endsection