@extends('portal.layouts.manager.master')

@section('page-title')Associate @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/sales-associate/view.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <associate id="{{ $associate["id"] }}"></associate>
    </div>
@endsection


