@extends('portal.layouts.manager.master')

@section('page-title')Add Partner @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/delivery-partner/add.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <partner></partner>
    </div>
@endsection


