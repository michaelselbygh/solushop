@extends('portal.layouts.manager.master')

@section('page-title')@endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/product/view.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <product id="{{ $product["id"] }}"></product>
    </div>
@endsection


