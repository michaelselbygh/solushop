@extends('portal.layouts.manager.master')

@section('page-title')Conversation @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/conversation/view.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <conversation id="{{ $conversation["id"] }}"></conversation>
    </div>
@endsection

