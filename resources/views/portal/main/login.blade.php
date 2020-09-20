
<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
        <meta name="description" content="Manage Solushop Ghana, your most trusted online store.">
        <meta name="author" content="Solushop Ghana Limited">
        <title> Login as {{ $entity }} </title>
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

        <script src="{{ asset('js/app.js') }}" defer></script>
        <script src="{{ asset('js/portal/commons.js') }}" defer></script>
        <script src="{{ asset('js/portal/page/login.js') }}" defer></script>
        

        <!-- Vue-->
        {{-- <script src="https://cdn.jsdelivr.net/npm/vue@2.6.0"></script> --}}
        {{-- <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script> --}}
        

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
                                <div class="card border-grey border-lighten-3 box-shadow-2 px-1 py-1" style="width:320px; height:420px; display: inline-block">
                                    <div class="card-header border-0">
                                        <div class="card-title text-center">
                                            <img :src="logoUrl" style="width: 180px; height: auto; padding-top: 40px; padding-bottom: 20px;" alt="New Lucky Logo">
                                            
                                        </div>
                                        <h6 class="card-subtitle line-on-side text-center font-small-3 pt-2"><span>Login as <b id="entity">{{ $entity }}</b></span></h6><br>
                                        
                                    </div>
                                    <div class="card-content">
                                        <div class="card-body" style="padding-top: 0px; padding-bottom: 15px;">
                                            <transition name="fade" mode="out-in">
                                                <div key="1" v-if="loading">
                                                    <img  style="text-align: center; vertical-align: middle; line-height: 320px; " :src="loaderUrl" alt="" > 
                                                    <br>
                                                    Logging you in,<br>
                                                    this will only take a second.
                                                </div>
                                            
                                                <div key="" v-else>
                                                    <form class="form-horizontal form-simple">
                                                        <fieldset class="form-group position-relative has-icon-left mb-0">
                                                            <input v-model="form.username" type="text" name="email" class="form-control form-control-lg input-lg" id="email" placeholder="Email / Username" required autocomplete="email" autofocus>
                                                            <div class="form-control-position">
                                                                <i class="ft-user"></i>
                                                            </div>
                                                        </fieldset>
                                                        <fieldset class="form-group position-relative has-icon-left">
                                                            <input v-model="form.password" type="password" name="password" class="form-control form-control-lg input-lg" id="password"
                                                                placeholder="Password / PIN" required autocomplete="current-password" autofocus>
                                                            <div class="form-control-position">
                                                                <i class="la la-key"></i>
                                                            </div>
                                                        </fieldset>
                                                        <fieldset>
                                                            <div class="custom-control custom-checkbox">
                                                                <input v-model="form.remember" type="checkbox" class="custom-control-input" id="customCheck1" name="remember">
                                                                <label class="custom-control-label" for="customCheck1">Remember Me</label>
                                                            </div>
                                                        </fieldset>
                                                    </form>
                                                    <div class="form-group row">
                                                    </div>
                                                    <button @click.prevent="login" name="login" class="btn btn-info btn-lg btn-block" style="background-color: #f68c20 !important; border-color: #f68c20 !important; border-radius: 10px;">
                                                        <i class="ft-unlock"></i> Login
                                                    </button>
                                                </div>
                                            </transition>
                                        </div>
                                    </div>
                                </div>
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
            var entity = document.getElementById('entity').innerHTML;
        </script>

        <style>
            .fade-enter-active, .fade-leave-active {
                transition: opacity .5s;
            }
            .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
                opacity: 0;
            }
        </style>

    </body>

</html>