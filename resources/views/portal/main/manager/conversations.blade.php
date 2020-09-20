@extends('portal.layouts.manager.master')

@section('page-title')Conversations @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/conversation/list.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <conversations></conversations>
    </div>
@endsection

