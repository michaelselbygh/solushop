@extends('portal.layouts.manager.master')

@section('page-title')Accounts @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/accounts.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <accounts></accounts>
    </div>
@endsection



