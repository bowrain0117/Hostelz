<section class="bg-second">
    <div class="container">
        <nav class="navbar navbar-expand-md navbar-nav navbar-dark header-navigation py-2">
            <div class="container-fluid row"> 
                <!-- Navbar Header  -->
                <span class="navbar-brand d-md-none d-lg-none"><span class="font-weight-bold text-uppercase">Dashboard</span></span>

                <!-- Toggler/collapsibe Button -->
                <button type="button" data-toggle="collapse" data-target="#collapsibleNavbar" aria-controls="#collapsibleNavbar" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler navbar-toggler-right"><i class="fa fa-bars"></i></button>
            
                <!-- Navbar links -->
                <div class="navbar-collapse collapse" id="collapsibleNavbar" style="">
                    <ul class="navbar-nav">
                        <!--  Single -->
                        <li class="nav-item">
                            <a class="nav-link font-weight-normal {{ (request()->is('user')) ? 'active' :'' }}" href="{!! routeURL('user:menu') !!}" id="" data-toggle="">{!! langGet('User.menu.UserMenu') !!}</a>
                        </li>
                        <!-- Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle font-weight-normal {{ (request()->is('user/settings/*')) ? 'active' : '' }}" href=""  id="" data-toggle="dropdown">Profile</a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item {{ (request()->is('user/settings/settings')) ? 'active' :'' }}" href="{!! routeURL('user:settings', 'settings') !!}">Settings</a>
                                <a class="dropdown-item {{ (request()->is('user/settings/profilePhoto')) ? 'active' :'' }}" href="{!! routeURL('user:settings', 'profilePhoto') !!}">Profile Photo</a>
                                <a class="dropdown-item {{ (request()->is('user/points')) ? 'active' :'' }}" href="{!! routeURL('user:settings', 'points') !!}">My Points</a>  
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{!! routeURL('logout') !!}">@langGet('global.logout')</a>                       
                            </div>
                        </li>
                        <!-- Single -->
                        <li class="nav-item">
                            <a class="nav-link font-weight-normal {{ (request()->is('wishlists/*')) ||  (request()->is('wishlists')) ? 'active' :'' }}" href="{!! routeURL('wishlist:index') !!}" id="" data-toggle="">My Wishlists</a>
                        </li>
                        
                        <!-- Dropdown -->
                        @if (auth()->user()->hasAnyPermissionOf([ 'reviewer', 'staffWriter', 'placeDescriptionWriter' ]))
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle font-weight-normal" href="#" id="" data-toggle="dropdown">{!! langGet('User.menu.TravelWriting') !!}</a>
                                <div class="dropdown-menu">
                                    @if (auth()->user()->hasPermission('reviewer'))
                                        <a class="dropdown-item" href="{!! routeURL('reviewer:reviews') !!}">@langGet('User.menu.HostelReviews')</a>
                                    @endif
                                    <a class="dropdown-item" href="{!! routeURL('submitCityPicsFindCity') !!}">@langGet('User.menu.SubmitCityPics')</a>
                                </div>
                            </li>
                        @endif
                        <!-- Dropdown -->
                        @if (auth()->user()->hasAnyPermissionOf([ 'staff', 'affiliate' ]))
                            <li class="nav-item dropdown">
                                <a class="nav-link font-weight-normal dropdown-toggle" href="#" id="" data-toggle="dropdown">{!! langGet('User.menu.SpecialAccess') !!}</a>
                                <div class="dropdown-menu">
                                    @if (auth()->user()->hasPermission('staff'))
                                        <a class="dropdown-item" href="{!! routeURL('staff-menu') !!}">Staff Menu</a>
                                    @endif
                                    @if (auth()->user()->hasPermission('affiliate'))
                                        <a class="dropdown-item" href="{!! routeURL('affiliate:menu') !!}">{!! langGet('User.menu.AffiliateProgram') !!}</a>
                                    @endif
                                </div>
                            </li>
                        @endif
                        <!-- Dropdown -->
                        @if (auth()->user()->mgmtListings)
                            <li class="nav-item">
                                <a class="nav-link font-weight-normal {{ (request()->is('mgmt')) ? 'active' :'' }}" href="{!! routeURL('mgmt:menu') !!}" id="" data-toggle="">{!! langGet('User.menu.ListingManagement') !!}</a>
                            </li>
                        @endif

                        </ul>
                    </div>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</section>