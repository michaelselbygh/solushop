@extends('portal.layouts.manager.master')

@section('page-title')Subscriptions @endsection

@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/subscriptions.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <subscriptions></subscriptions>
    </div>
@endsection



