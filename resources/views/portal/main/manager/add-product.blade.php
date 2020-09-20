@extends('portal.layouts.manager.master')

@section('page-title')Add Product @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/product/add.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <product></product>
    </div>
@endsection


