const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/app/commons.js', 'public/js/app')
    .js('resources/js/portal/commons.js', 'public/js/portal')
    .js('resources/js/portal/page/login.js', 'public/js/portal/page')
    .js('resources/js/portal/page/manager/dashboard.js', 'public/js/portal/page/manager')
    .js('resources/js/portal/page/manager/product/dashboard.js', 'public/js/portal/page/manager/product')
    .js('resources/js/portal/page/manager/product/add.js', 'public/js/portal/page/manager/product')
    .js('resources/js/portal/page/manager/product/view.js', 'public/js/portal/page/manager/product')
    .js('resources/js/portal/page/manager/customer/list.js', 'public/js/portal/page/manager/customer/')
    .js('resources/js/portal/page/manager/customer/view.js', 'public/js/portal/page/manager/customer/')
    .js('resources/js/portal/page/manager/order.js', 'public/js/portal/page/manager')
    .js('resources/js/portal/page/manager/activity.js', 'public/js/portal/page/manager')
    .js('resources/js/portal/page/manager/sms.js', 'public/js/portal/page/manager')
    .js('resources/js/portal/page/manager/accounts.js', 'public/js/portal/page/manager')
    .js('resources/js/portal/page/manager/subscriptions.js', 'public/js/portal/page/manager')
    .js('resources/js/portal/page/manager/conversation/list.js', 'public/js/portal/page/manager/conversation/')
    .js('resources/js/portal/page/manager/conversation/view.js', 'public/js/portal/page/manager/conversation/')
    .js('resources/js/portal/page/manager/flags.js', 'public/js/portal/page/manager/')
    .js('resources/js/portal/page/manager/sales-associate/add.js', 'public/js/portal/page/manager/sales-associate/')
    .js('resources/js/portal/page/manager/sales-associate/list.js', 'public/js/portal/page/manager/sales-associate/')
    .js('resources/js/portal/page/manager/sales-associate/view.js', 'public/js/portal/page/manager/sales-associate/')
    .js('resources/js/portal/page/manager/delivery-partner/add.js', 'public/js/portal/page/manager/delivery-partner/')
    .js('resources/js/portal/page/manager/delivery-partner/list.js', 'public/js/portal/page/manager/delivery-partner/')
    .js('resources/js/portal/page/manager/delivery-partner/view.js', 'public/js/portal/page/manager/delivery-partner/')    
    .js('resources/js/portal/page/manager/vendor/add.js', 'public/js/portal/page/manager/vendor/')
    .js('resources/js/portal/page/manager/vendor/list.js', 'public/js/portal/page/manager/vendor/')
    .js('resources/js/portal/page/manager/vendor/view.js', 'public/js/portal/page/manager/vendor/')
    .js('resources/js/portal/page/manager/logistics/active.js', 'public/js/portal/page/manager/logistics/')
    .js('resources/js/portal/page/manager/logistics/history.js', 'public/js/portal/page/manager/logistics/')
    .js('resources/js/portal/page/manager/coupon/list.js', 'public/js/portal/page/manager/coupon/')
    .js('resources/js/portal/page/manager/coupon/generate.js', 'public/js/portal/page/manager/coupon/')
    .sass('resources/sass/app.scss', 'public/css');
