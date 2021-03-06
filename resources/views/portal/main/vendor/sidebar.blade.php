<div class="main-menu menu-fixed menu-dark menu-accordion    menu-shadow " data-scroll-to-active="true">
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            <li class="nav-item">
                <a href="{{ route('vendor.dashboard') }}">
                    <i class="la la-home" style="color:#f68b1e;"></i>
                    <span class="menu-title">Dashboard</span>
                </a>
            </li>
            <li class=" nav-item"><a href="{{ route('vendor.show.products') }}"><i class="la la-archive" style="color:#f68b1e;"></i><span class="menu-title">Products</span></a>
                <ul class="menu-content">
                    <li>
                        <a class="menu-item" href="{{ route('vendor.show.products') }}">
                            <span>Manage Products</span>
                        </a>
                    </li>
                    <li>
                        <a class="menu-item" href="{{ route('vendor.show.add.product') }}">
                            <span>Add Product</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="{{ route('vendor.show.orders') }}">
                    <i class="la la-shopping-cart" style="color:#f68b1e;"></i>
                    <span class="menu-title">Orders</span>
                </a>
            </li>
            {{-- <li class="nav-item">
                <a href="{{ route('vendor.show.subscription') }}" >
                    <i class="la la-ticket" style="color:#f68b1e;"></i>
                    <span class="menu-title">Subscription</span>
                </a>
            </li> --}}
            <li class="nav-item">
                <a href="{{ route('vendor.show.conversations') }}" >
                    <i class="la la-envelope" style="color:#f68b1e;"></i>
                    <span class="menu-title">
                        Messages
                        @if(isset($vendor["unread_messages"]) and $vendor["unread_messages"]>0)
                            <span style='color:white; background-color: red; padding: 4px 8px; border-radius:20px; margin-left:5px;'>
                                {{ $vendor["unread_messages"] }}
                            </span>
                        @endif
                    </span>

                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('vendor.show.terms.of.use') }}">
                    <i class="la la-exclamation-circle" style="color:#f68b1e;"></i>
                    <span class="menu-title">Terms of Use</span>
                </a>
            </li>
            @if (Auth::guard('vendor')->user())
                <li class="nav-item">
                    <a href="{{ route("vendor.logout") }}">
                        <i class="la la-lock" style="color:#f68b1e;"></i>
                        <span class="menu-title">Logout</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
    