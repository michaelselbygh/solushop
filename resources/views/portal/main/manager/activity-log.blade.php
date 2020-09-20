@extends('portal.layouts.manager.master')

@section('page-title')Activity @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/activity.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <activity></activity>
    </div>
@endsection



