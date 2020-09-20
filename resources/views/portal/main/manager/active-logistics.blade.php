@extends('portal.layouts.manager.master')

@section('page-title')Logistics Tracking @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/logistics/active.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <logistics></logistics>
    </div>
@endsection



