@extends('portal.layouts.manager.master')

@section('page-title')Vendor @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/vendor/view.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <vendor id="{{ $vendor["id"] }}"></vendor>
    </div>
@endsection


