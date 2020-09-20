<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Auth::routes();



//home and auth routes
Route::get('/', 'AppHomeController@showHome')->name('home');
Route::get('/logout', 'Auth\LoginController@logout')->name('customer.logout');
Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
Route::get('/register', 'Auth\LoginController@showRegisterForm')->name('register');
Route::post('/login', 'Auth\LoginController@login')->name('customer.login.submit');
Route::get('/reset-password', 'Auth\LoginController@showResetPasswordForm')->name('customer.reset.password');
Route::post('/reset-password', 'Auth\LoginController@resetPassword')->name('customer.reset.password.submit');

//shop, products and vendors routes
Route::get('/shop/category/{categorySlug}', 'AppShopController@showCategoryProducts')->name('show.shop.category');
Route::get('/shop/{vendorSlug}/{productSlug}', 'AppProductController@showProduct')->name('show.product');
Route::post('/shop/{vendorSlug}/{productSlug}', 'AppProductController@processProductAction')->name('process.product.action');
Route::get('/shop/{vendorSlug}', 'AppVendorController@showVendor')->name('show.vendor');
Route::get('/shops', 'AppVendorController@showVendors')->name('show.vendors');
Route::get('/shop', 'AppShopController@showProducts')->name('show.shop');
Route::post('/shop', 'AppShopController@showSearchProducts')->name('show.shop.search');

//my account routes
Route::get('/my-account', 'AppMyAccountController@showDashboard')->name('show.account.dashboard');
Route::get('/my-account/messages', 'AppMyAccountController@showMessages')->name('show.account.messages');
Route::get('/my-account/messages/{vendorSlug}/{productSlug?}', 'AppMyAccountController@showConversation')->name('show.account.conversation');
Route::post('/my-account/messages/{vendorSlug}/{productSlug?}', 'AppMyAccountController@processConversation')->name('process.account.conversation');
Route::get('/my-account/personal-details', 'AppMyAccountController@showPersonalDetails')->name('show.account.personal.details');
Route::post('/my-account/personal-details', 'AppMyAccountController@processPersonalDetails')->name('process.account.personal.details');
Route::get('/my-account/orders', 'AppMyAccountController@showOrders')->name('show.account.orders');
Route::post('/my-account/orders', 'AppMyAccountController@processOrders')->name('process.account.orders');
Route::get('/my-account/login-and-security', 'AppMyAccountController@showLoginAndSecurity')->name('show.account.login.and.security');
Route::post('/my-account/login-and-security', 'AppMyAccountController@processLoginAndSecurity')->name('process.account.login.and.security');
Route::get('/my-account/addresses', 'AppMyAccountController@showAddresses')->name('show.account.addresses');
Route::get('/my-account/s-wallet', 'AppMyAccountController@showWallet')->name('show.account.wallet');
Route::get('/my-account/addresses/add', 'AppMyAccountController@showAddAddress')->name('show.account.add.address');
Route::post('/my-account/addresses/add', 'AppMyAccountController@processAddAddress')->name('process.account.add.address');
Route::post('/my-account/addresses', 'AppMyAccountController@processEditAddress')->name('process.account.edit.address');
Route::post('/my-account/s-wallet', 'AppMyAccountController@processWallet')->name('process.account.wallet');

//general pages 
Route::get('/wishlist', 'AppGeneralPagesController@showWishlist')->name('show.wishlist');
Route::post('/wishlist', 'AppGeneralPagesController@processWishlistAction')->name('process.wishlist.action');
Route::get('/cart', 'AppGeneralPagesController@showCart')->name('show.cart');
Route::post('/cart', 'AppGeneralPagesController@processCartAction')->name('process.cart.action');
Route::get('/checkout', 'AppGeneralPagesController@showCheckout')->name('show.checkout');
Route::post('/checkout', 'AppGeneralPagesController@processCheckout')->name('process.checkout');
Route::get('/about', 'AppGeneralPagesController@showAbout')->name('show.about');
Route::get('/contact', 'AppGeneralPagesController@showContact')->name('show.contact');
Route::get('/terms-and-conditions', 'AppGeneralPagesController@showTNC')->name('show.terms.and.conditions');
Route::get('/privacy-policy', 'AppGeneralPagesController@showPrivacyPolicy')->name('show.privacy.policy');
Route::get('/return-policy', 'AppGeneralPagesController@showReturnPolicy')->name('show.return.policy');
Route::get('/frequently-asked-questions', 'AppGeneralPagesController@showFAQ')->name('show.frequently.asked.questions');
Route::get('/page-not-found', 'AppGeneralPagesController@show404')->name('page.not.found');

//crons for testing purposes
Route::get('/cron/reports', 'CronsController@generateReports');
Route::get('/cron/process-sms-queue', 'CronsController@processSMSQueue');
Route::get('/cron/update-counts', 'CronsController@updateCounts');
Route::get('/cron/vendor-subscriptions-check', 'CronsController@checkVendorSubscriptions');
Route::get('/cron/delete-empty-conversations', 'CronsController@deleteEmptyConversations');
Route::get('/cron/delete-unpaid-orders', 'CronsController@deleteUnpaidOrders');
Route::get('/cron/delete-unpaid-wtu-payments', 'CronsController@deleteUnpaidWTUPayments');
Route::get('/cron/update-expired-coupons', 'CronsController@updateExpiredCoupons');
Route::get('/cron/pending-approvals-check', 'CronsController@checkForPendingApprovals');




//receive call back from slydepay
Route::get('receive-callback', 'AppGeneralPagesController@processReceiveCallback');

//portal routes
Route::prefix('portal')->group(function(){
    //manager routes
    Route::prefix('manager')->group(function(){
        //login routes
        Route::get('/login', 'Auth\ManagerLoginController@showLoginForm')->name('manager.login');
        Route::post('/login', 'Auth\ManagerLoginController@login')->name('manager.login.submit');
        Route::get('/logout', 'Auth\ManagerLoginController@logout')->name('manager.logout');

        //broadcasts
        Route::get('/broadcast/vendors', 'ManagerController@broadcastVendors');
        Route::get('/broadcast/customers', 'ManagerController@broadcastCustomers');


        //guides
        Route::get('/guide/delivery', 'ManagerController@showDeliveryGuide')->name('manager.show.delivery.guide');
        Route::get('/guide/pick-up', 'ManagerController@showPickUpGuide')->name('manager.show.pick.up.guide');

        //logistics
        Route::get('/logistics/active', 'ManagerController@showLogisticsActive')->name('manager.show.logistics.active');
        Route::get('/logistics/history', 'ManagerController@showLogisticsHistory')->name('manager.show.logistics.history');
        Route::post('logistics/active/count', 'Manager\LogisticsController@getActiveLogisticsCount')->name('endpoint.get.active.logistics.count');
        Route::post('logistics/active/records', 'Manager\LogisticsController@getActiveLogisticsRecords')->name('endpoint.get.active.logistics.records');
        Route::post('logistics/active/action', 'Manager\LogisticsController@processLogisticsAction')->name('endpoint.process.logistics.action');
        Route::post('logistics/history/count', 'Manager\LogisticsController@getHistoryLogisticsCount')->name('endpoint.get.history.logistics.count');
        Route::post('logistics/history/records', 'Manager\LogisticsController@getHistoryLogisticsRecords')->name('endpoint.get.history.logistics.records');

        //pages
        Route::get('pick-ups/history', 'ManagerController@showPickupHistory')->name('manager.show.pick.ups.history');
        Route::get('pick-ups/active', 'ManagerController@showActivePickups')->name('manager.show.active.pick.ups');
        Route::post('pick-ups/active', 'ManagerController@processActivePickups')->name('manager.process.active.pick.ups');
        Route::get('deliveries/history', 'ManagerController@showDeliveryHistory')->name('manager.show.deliveries.history');
        Route::get('deliveries/active', 'ManagerController@showActiveDeliveries')->name('manager.show.active.deliveries');
        Route::post('deliveries/active', 'ManagerController@processActiveDeliveries')->name('manager.process.active.deliveries');

        //products
        Route::get('/products', 'ManagerController@showProducts')->name('manager.show.products');
        Route::post('/products/count', 'Manager\ProductsController@getProductsCount')->name('endpoint.get.products.count');
        Route::post('/products/records', 'Manager\ProductsController@getProductsRecords')->name('endpoint.get.products.records');
        Route::post('/products/action', 'Manager\ProductsController@doProductsAction')->name('endpoint.do.products.action');
        Route::post('/product/action', 'Manager\ProductsController@doProductAction')->name('endpoint.do.product.action');
        Route::get('/product/add', 'ManagerController@showAddProduct')->name('manager.show.add.product');
        Route::post('/product/add', 'Manager\ProductsController@addProduct')->name('manager.add.product');
        Route::post('/product/options', 'Manager\ProductsController@getProductOptions')->name('endpoint.get.product.options');
        Route::post('/product/count', 'Manager\ProductsController@getProductCount')->name('endpoint.get.product.count');
        Route::post('/product/records', 'Manager\ProductsController@getProductRecords')->name('endpoint.get.product.records');
        Route::post('/product/update-records', 'Manager\ProductsController@updateProductRecord')->name('endpoint.update.product.record');
        Route::post('/product/update-stock', 'Manager\ProductsController@updateProductStock')->name('endpoint.update.product.stock');
        Route::post('/product/update-badges', 'Manager\ProductsController@updateProductBadges')->name('endpoint.update.product.badges');
        Route::post('/product/delete-image', 'Manager\ProductsController@deleteProductImage')->name('endpoint.delete.product.image');
        Route::post('/product/add-images', 'Manager\ProductsController@addProductImages')->name('endpoint.add.product.images');
        Route::get('/products/update-stock-prices', 'Manager\ProductsController@updateStockPrices')->name('manager.update.stock.prices');
        Route::get('/product/{productID}', 'ManagerController@showProduct')->name('manager.show.product');



        //orders
        
        Route::post('/order/count', 'Manager\OrdersController@getOrderCount')->name('endpoint.get.order.count');
        Route::post('/order/records', 'Manager\OrdersController@getOrderRecords')->name('endpoint.get.order.records');
        Route::post('/order/action', 'Manager\OrdersController@processOrderAction')->name('endpoint.process.order.action');
        Route::post('/order/shipping', 'Manager\OrdersController@processOrderShipping')->name('endpoint.process.order.shipping');
        Route::get('/order/{orderID}', 'ManagerController@showOrder')->name('manager.show.order');
        Route::get('/orders', 'ManagerController@showOrders')->name('manager.show.orders');
        Route::post('/orders/count', 'Manager\OrdersController@getOrdersCount')->name('endpoint.get.orders.count');
        Route::post('/orders/records', 'Manager\OrdersController@getOrdersRecords')->name('endpoint.get.orders.records');

        //customers
        Route::get('/customer/{customerID}', 'ManagerController@showCustomer')->name('manager.show.customer');
        Route::post('/customer/count', 'Manager\CustomersController@getCustomerCount')->name('endpoint.get.customer.count');
        Route::post('/customer/records', 'Manager\CustomersController@getCustomerRecords')->name('endpoint.get.customer.records');
        Route::post('/customer/update-records', 'Manager\CustomersController@updateCustomerRecord')->name('endpoint.update.customer.record');
        Route::post('/customer/record-payment', 'Manager\CustomersController@recordCustomerPayment')->name('endpoint.record.customer.payment');
        Route::get('/customers', 'ManagerController@showCustomers')->name('manager.show.customers');
        Route::post('/customers/count', 'Manager\CustomersController@getCustomersCount')->name('endpoint.get.customers.count');
        Route::post('/customers/records', 'Manager\CustomersController@getCustomersRecords')->name('endpoint.get.customers.records');

        //sms
        Route::get('/sms-report', 'ManagerController@showSMSReport')->name('manager.sms.report');
        Route::post('/sms/count', 'Manager\ReportsController@getSMSCount')->name('endpoint.get.sms.count');
        Route::post('/sms/records', 'Manager\ReportsController@getSMSRecords')->name('endpoint.get.sms.records');

        //activity
        Route::get('/activity-log', 'ManagerController@showActivityLog')->name('manager.activity.log');
        Route::post('/activity/count', 'Manager\ReportsController@getActivityCount')->name('endpoint.get.activity.count');
        Route::post('/activity/records', 'Manager\ReportsController@getActivityRecords')->name('endpoint.get.activity.records');

        //accounts
        Route::get('/accounts', 'ManagerController@showAccounts')->name('manager.show.accounts');
        Route::post('/accounts/count', 'Manager\AccountsController@getAccountsCount')->name('endpoint.get.accounts.count');
        Route::post('/accounts/records', 'Manager\AccountsController@getAccountsRecords')->name('endpoint.get.accounts.records');
        Route::post('/accounts/record-payment', 'Manager\AccountsController@recordAccountsPayment')->name('endpoint.record.accounts.payment');

        //subscriptions
        // Route::get('/subscriptions', 'ManagerController@showSubscriptions')->name('manager.subscriptions');
        // Route::post('/subscriptions/count', 'Manager\SubscriptionsController@getSubscriptionsCount')->name('endpoint.get.subscriptions.count');
        // Route::post('/subscriptions/records', 'Manager\SubscriptionsController@getSubscriptionsRecords')->name('endpoint.get.subscriptions.records');
        // Route::post('/subscriptions/cancel', 'Manager\SubscriptionsController@cancelSubscription')->name('endpoint.cancel.subscription');

        //conversations
        Route::get('/conversations', 'ManagerController@showMessages')->name('manager.show.messages');
        Route::post('/conversations/count', 'Manager\ConversationsController@getConversationsCount')->name('endpoint.get.conversations.count');
        Route::post('/conversations/records', 'Manager\ConversationsController@getConversationsRecords')->name('endpoint.get.conversations.records');
        Route::get('/conversation/{conversationID}', 'ManagerController@showConversation')->name('manager.show.conversation');
        Route::post('/conversation/count', 'Manager\ConversationsController@getConversationCount')->name('endpoint.get.conversation.count');
        Route::post('/conversation/records', 'Manager\ConversationsController@getConversationRecords')->name('endpoint.get.conversation.records');
        Route::post('/conversation/send', 'Manager\ConversationsController@sendMessage')->name('endpoint.send.message');

        //flags
        Route::get('/messages/flags', 'ManagerController@showFlaggedMessages')->name('manager.show.flagged.messages');
        Route::post('/messages/flags/count', 'Manager\FlagsController@getFlagsCount')->name('endpoint.get.flags.count');
        Route::post('/messages/flags/records', 'Manager\FlagsController@getFlagsRecords')->name('endpoint.get.flags.records');
        Route::post('/messages/flags/action', 'Manager\FlagsController@processFlag')->name('endpoint.process.flag');

        //sales-associates
        Route::get('/sales-associate/{memberID}', 'ManagerController@showSalesAssociate')->name('manager.show.sales.associate');
        Route::post('/sales-associate/count', 'Manager\SalesAssociatesController@getAssociateCount')->name('endpoint.get.sales.associate.count');
        Route::post('/sales-associate/records', 'Manager\SalesAssociatesController@getAssociateRecords')->name('endpoint.get.sales.associate.records');
        Route::post('/sales-associate/update-records', 'Manager\SalesAssociatesController@updateAssociateRecord')->name('endpoint.update.sales.associate.record');
        Route::post('/sales-associate/record-payment', 'Manager\SalesAssociatesController@recordAssociatePayment')->name('endpoint.record.sales.associate.payment');
        Route::get('/sales-associates', 'ManagerController@showSalesAssociates')->name('manager.show.sales.associates');
        Route::get('/sales-associates/add', 'ManagerController@showAddSalesAssociate')->name('manager.show.add.sales.associate');
        Route::post('/sales-associates/add', 'Manager\SalesAssociatesController@addSalesAssociate')->name('manager.add.sales.associate');
        Route::post('/sales-associates/count', 'Manager\SalesAssociatesController@getSalesAssociateCount')->name('endpoint.get.sales-associates.count');
        Route::post('/sales-associates/records', 'Manager\SalesAssociatesController@getSalesAssociateRecords')->name('endpoint.get.sales-associates.records');

        //delivery-partners
        Route::get('/delivery-partner/{partnerID}', 'ManagerController@showDeliveryPartner')->name('manager.show.delivery.partner');
        Route::post('/delivery-partner/count', 'Manager\DeliveryPartnersController@getPartnerCount')->name('endpoint.get.delivery.partner.count');
        Route::post('/delivery-partner/records', 'Manager\DeliveryPartnersController@getPartnerRecords')->name('endpoint.get.delivery.partner.records');
        Route::post('/delivery-partner/update-records', 'Manager\DeliveryPartnersController@updatePartnerRecord')->name('endpoint.update.delivery.partner.record');
        Route::post('/delivery-partner/record-payment', 'Manager\DeliveryPartnersController@recordPartnerPayment')->name('endpoint.record.delivery.partner.payment');
        Route::get('/delivery-partners', 'ManagerController@showDeliveryPartners')->name('manager.show.delivery.partners');
        Route::get('/delivery-partners/add', 'ManagerController@showAddDeliveryPartner')->name('manager.show.add.delivery.partner');
        Route::post('/delivery-partners/add', 'Manager\DeliveryPartnersController@addDeliveryPartner')->name('manager.add.delivery.partner');
        Route::post('/delivery-partners/count', 'Manager\DeliveryPartnersController@getDeliveryPartnerCount')->name('endpoint.get.delivery.partners.count');
        Route::post('/delivery-partners/records', 'Manager\DeliveryPartnersController@getDeliveryPartnerRecords')->name('endpoint.get.delivery.partners.records');

        //vendors
        Route::get('/vendor/{vendorID}', 'ManagerController@showVendor')->name('manager.show.vendor');
        Route::post('/vendor/count', 'Manager\VendorsController@getVendorCount')->name('endpoint.get.vendor.count');
        Route::post('/vendor/records', 'Manager\VendorsController@getVendorRecords')->name('endpoint.get.vendor.records');
        Route::post('/vendor/update-records', 'Manager\VendorsController@updateVendorRecord')->name('endpoint.update.vendor.record');
        Route::post('/vendor/record-payment', 'Manager\VendorsController@recordVendorPayment')->name('endpoint.record.vendor.payment');
        Route::get('/vendors', 'ManagerController@showVendors')->name('manager.show.vendors');
        Route::get('/vendors/add', 'ManagerController@showAddVendor')->name('manager.show.add.vendor');
        Route::post('/vendors/add', 'Manager\VendorsController@addVendor')->name('manager.add.vendor');
        Route::post('/vendors/count', 'Manager\VendorsController@getVendorsCount')->name('endpoint.get.vendors.count');
        Route::post('/vendors/records', 'Manager\VendorsController@getVendorsRecords')->name('endpoint.get.vendors.records');

        //coupons
        Route::get('coupons', 'ManagerController@showCoupons')->name('manager.show.coupons');
        Route::post('/coupons/count', 'Manager\CouponsController@getCouponsCount')->name('endpoint.get.coupons.count');
        Route::post('/coupons/records', 'Manager\CouponsController@getCouponsRecords')->name('endpoint.get.coupons.records');
        Route::post('/coupon/generate', 'Manager\CouponsController@generateCoupon')->name('endpoint.generate.coupon');
        Route::get('coupons/generate', 'ManagerController@showGenerateCoupon')->name('manager.show.generate.coupon');

        //scripts
        Route::get('scripts/updateSKUs', 'Manager\ScriptsController@updateSKUs')->name('manager.show.update.skus');


        //dashboard
        Route::get('/', 'ManagerController@index')->name('manager.dashboard');


    });

    //vendor routes
    Route::prefix('vendor')->group(function(){
        Route::get('/login', 'Auth\VendorLoginController@showLoginForm')->name('vendor.login');
        Route::post('/login', 'Auth\VendorLoginController@login')->name('vendor.login.submit');
        Route::get('/logout', 'Auth\VendorLoginController@logout')->name('vendor.logout');
        Route::get('/products', 'VendorController@showProducts')->name('vendor.show.products');
        Route::post('/products', 'VendorController@processProducts')->name('vendor.process.products');
        Route::get('/products/add', 'VendorController@showAddProduct')->name('vendor.show.add.product');
        Route::post('/products/add', 'VendorController@processAddProduct')->name('vendor.process.add.product');
        Route::get('/product/{productSlug}', 'VendorController@showProduct')->name('vendor.show.product');
        Route::post('/product/{productSlug}', 'VendorController@processProduct')->name('vendor.process.product');
        Route::get('/orders', 'VendorController@showOrders')->name('vendor.show.orders');
        Route::get('/conversation/{conversationID}', 'VendorController@showConversation')->name('vendor.show.conversation');
        Route::post('/conversation/{conversationID}', 'VendorController@processConversation')->name('vendor.process.conversation');
        Route::get('/conversations', 'VendorController@showConversations')->name('vendor.show.conversations');
        Route::get('/terms-of-use', 'VendorController@showTermsOfUse')->name('vendor.show.terms.of.use');
        // Route::get('/subscription', 'VendorController@showSubscription')->name('vendor.show.subscription');
        // Route::post('/subscription', 'VendorController@processSubscription')->name('vendor.process.subscription');
        Route::get('/', 'VendorController@index')->name('vendor.dashboard');
    });

    //sales associate routes
    Route::prefix('sales-associate')->group(function(){
        Route::get('/login', 'Auth\SalesAssociateLoginController@showLoginForm')->name('sales-associate.login');
        Route::post('/login', 'Auth\SalesAssociateLoginController@login')->name('sales-associate.login.submit');
        Route::get('/logout', 'Auth\SalesAssociateLoginController@logout')->name('sales-associate.logout');
        Route::get('/terms-of-use', 'SalesAssociateController@showTermsOfUse')->name('sales-associate.show.terms.of.use');
        Route::get('/customers', 'SalesAssociateController@showCustomers')->name('sales-associate.show.customers');
        Route::get('/customers/add', 'SalesAssociateController@showAddCustomer')->name('sales-associate.show.add.customer');
        Route::post('/customers/add', 'SalesAssociateController@processAddCustomer')->name('sales-associate.process.add.customer');
        Route::get('/orders', 'SalesAssociateController@showOrders')->name('sales-associate.show.orders');
        Route::get('/order/{orderID}', 'SalesAssociateController@showOrder')->name('sales-associate.show.order');
        Route::get('/orders/add', 'SalesAssociateController@showAddOrderOne')->name('sales-associate.show.add.order.step-1');
        Route::get('/orders/add/{customerID}', 'SalesAssociateController@showAddOrderTwo')->name('sales-associate.show.add.order.step-2');
        Route::post('/orders/add/{customerID}', 'SalesAssociateController@processAddOrderTwo')->name('sales-associate.process.add.order.step-2');
        Route::get('/orders/add/{customerID}/{addressID}', 'SalesAssociateController@showAddOrderThree')->name('sales-associate.show.add.order.step-3');
        Route::post('/orders/add/{customerID}/{addressID}', 'SalesAssociateController@processAddOrder')->name('sales-associate.process.add.order');
        Route::get('/', 'SalesAssociateController@index')->name('sales-associate.dashboard');
    });

    //delivery partner routes
    Route::prefix('delivery-partner')->group(function(){
        Route::get('/login', 'Auth\DeliveryPartnerLoginController@showLoginForm')->name('delivery-partner.login');
        Route::post('/login', 'Auth\DeliveryPartnerLoginController@login')->name('delivery-partner.login.submit');
        Route::get('/logout', 'Auth\DeliveryPartnerLoginController@logout')->name('delivery-partner.logout');
        Route::get('pick-ups', 'DeliveryPartnerController@showPickups')->name('delivery-partner.show.pick.ups');
        Route::post('pick-ups', 'DeliveryPartnerController@processPickups')->name('delivery-partner.process.pick.ups');
        Route::get('deliveries', 'DeliveryPartnerController@showDeliveries')->name('delivery-partner.show.deliveries');
        Route::post('deliveries', 'DeliveryPartnerController@processDeliveries')->name('delivery-partner.process.deliveries');
        Route::get('/', 'DeliveryPartnerController@index')->name('delivery-partner.dashboard');
    });
});

