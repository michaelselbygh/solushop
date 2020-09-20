@extends('portal.layouts.manager.master')

@section('page-title')Customer @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/customer/view.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <customer id="{{ $customer["id"] }}"></customer>
    </div>
@endsection

