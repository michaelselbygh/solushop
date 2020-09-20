
<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
        <meta name="description" content="Manage Solushop Ghana, your most trusted online store.">
        <meta name="author" content="Solushop Ghana Limited">
        <title> Login as @yield('entity') </title>
        <link rel="apple-touch-icon" href="{{ url("portal/images/ico/apple-icon-120.png") }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ url("portal/images/ico/favicon-32.png") }}">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i%7CQuicksand:300,400,500,700" rel="stylesheet">

        <!-- Vendor CSS-->
        <link rel="stylesheet" type="text/css" href="{{ url("portal/vendors/css/vendors.min.css") }}">
        <link rel="stylesheet" type="text/css" href="{{ url("portal/vendors/css/forms/icheck/icheck.css") }}">
        <link rel="stylesheet" type="text/css" href="{{ url("portal/vendors/css/forms/icheck/custom.css") }}">
        <!-- END: Vendor CSS-->

        <!-- Theme CSS-->
        <link rel="stylesheet" type="text/css" href="{{ url("portal/css/bootstrap.css") }}">
        <link rel="stylesheet" type="text/css" href="{{ url("portal/css/bootstrap-extended.css") }}">
        <link rel="stylesheet" type="text/css" href="{{ url("portal/css/colors.css") }}">
        <link rel="stylesheet" type="text/css" href="{{ url("portal/css/components.css") }}">
        <!-- END: Theme CSS-->

        <!-- Page CSS-->
        <link rel="stylesheet" type="text/css" href="{{ url("portal/css/core/menu/menu-types/vertical-content-menu.css") }}">
        <link rel="stylesheet" type="text/css" href="{{ url("portal/css/core/colors/palette-gradient.css") }}">
        <link rel="stylesheet" type="text/css" href="{{ url("portal/css/pages/login-register.css") }}">
        <!-- END: Page CSS-->

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Vue-->
        {{-- <script src="https://cdn.jsdelivr.net/npm/vue@2.6.0"></script> --}}
        <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>

        <style>
            input:focus{
                border-color: #f68c20 !important;
            }
        </style>

    </head>

    <body class="vertical-layout vertical-content-menu 1-column bg-full-screen-image blank-page" data-open="click" data-menu="vertical-content-menu" data-col="1-column">
        <!-- Content-->
        <div class="app-content content" id="portal-login-section">
            <div class="content-wrapper">
                <div class="content-header row mb-1">
                </div>
                <div class="content-body">
                    <section class="flexbox-container">
                        <div class="col-12 d-flex align-items-center justify-content-center">
                            <div class="col-sm-3" style="border-radius: 50px; text-align:center;">
                                <portal-login entity="@yield('entity')"></portal-login>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <!-- END: Content-->


        <!-- Vendor JS-->
        <script src="{{ url("portal/vendors/js/vendors.min.js") }}"></script>
        <!-- BEGIN Vendor JS-->

        <!-- Page Vendor JS-->
        <script src="{{ url("portal/vendors/js/ui/headroom.min.js") }}"></script>
        <script src="{{ url("portal/vendors/js/forms/validation/jqBootstrapValidation.js") }}"></script>
        <script src="{{ url("portal/vendors/js/forms/icheck/icheck.min.js") }}"></script>
        <!-- END: Page Vendor JS-->

        <!-- Theme JS-->
        <script src="{{ url("portal/js/core/app-menu.js") }}"></script>
        <script src="{{ url("portal/js/core/app.js") }}"></script>
        <!-- END: Theme JS-->

        <!-- Page JS-->
        <script src="{{ url("portal/js/scripts/forms/form-login-register.js") }}"></script>
        <!-- END: Page JS-->

        <script>
            // import portalLoginComponent from './../../../../js/components/PortalLogin'

            var portalLogin = new Vue({
                el: "#portal-login-section",
                data: {
                    test: true
                }
                // componets: {
                //     'portal-login': portalLoginComponent
                // }
            })
        </script>

    </body>

</html>