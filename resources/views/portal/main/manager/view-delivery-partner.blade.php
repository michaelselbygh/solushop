@extends('portal.layouts.manager.master')

@section('page-title')Partner @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/delivery-partner/view.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <partner id="{{ $partner["id"] }}"></partner>
    </div>
@endsection


