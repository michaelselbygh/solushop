@extends('portal.layouts.manager.master')

@section('page-title')Flags @endsection

@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/flags.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <flags></flags>
    </div>
@endsection