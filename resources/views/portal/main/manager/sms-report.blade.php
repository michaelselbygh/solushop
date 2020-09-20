@extends('portal.layouts.manager.master')

@section('page-title')SMS @endsection
@section('page-scripts')
    <script src="{{ asset('js/portal/page/manager/sms.js') }}" defer></script>
@endsection

@section('content-body')
    <div id="page" class="app-content content">
        <sms></sms>
    </div>
@endsection
    
    

