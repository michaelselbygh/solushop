 @extends('portal.layouts.manager.master')

 @section('page-title')Add Vendor @endsection
 @section('page-scripts')
     <script src="{{ asset('js/portal/page/manager/vendor/add.js') }}" defer></script>
 @endsection
 
 @section('content-body')
     <div id="page" class="app-content content">
         <vendor></vendor>
     </div>
 @endsection
 
 
 