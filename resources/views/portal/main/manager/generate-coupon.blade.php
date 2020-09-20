@extends('portal.layouts.manager.master')

@section('page-title')Generate Coupon @endsection

@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/coupon/generate.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <generate-coupon></generate-coupon>
    </div>
@endsection

