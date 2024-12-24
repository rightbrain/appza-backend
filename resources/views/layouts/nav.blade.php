<div class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-logo"><img src="{{ asset('Appza.png') }}" width="75%" alt=""></a>
    </div>
    <!-- sidebar-header -->
    <div class="sidebar-search">
        <div class="search-body">
            <i data-feather="home"></i>
            <a href="" style="color: #000000b3">Dashboard</a>
            {{--
            <input type="text" class="form-control" placeholder="Search...">--}}
        </div><!-- search-body -->
    </div><!-- sidebar-search -->
    <div class="sidebar-body pt-20">


        <div class="nav-group {{ Request::is('appza/layout-type/*') ? 'show' : ''}}">
            <div class="nav-group-label" style="font-size: 15px !important;">{{__('messages.layoutType')}}</div>
            <ul class="nav-sidebar">
                <li class="nav-item ">
                    <a href="{{route('layout_type_list')}}" class="nav-link {{ Request::is('appza/layout-type/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.layoutType')}}</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group {{ Request::is('appza/style-group/*') ? 'show' : ''}}">
            <div class="nav-group-label" style="font-size: 15px !important;">{{__('messages.style-group')}}</div>
            <ul class="nav-sidebar">
                <li class="nav-item ">
                    <a href="{{route('style_group_list')}}" class="nav-link {{ Request::is('appza/style-group/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.styleGroupList')}}</span></a>
                </li>
            </ul>
        </div>

        <div class="nav-group {{ Request::is('appza/page/*') ? 'show' : ''}}">
            <div class="nav-group-label" style="font-size: 15px !important;">{{__('messages.pages')}}</div>
            <ul class="nav-sidebar">
                <li class="nav-item ">
                    <a href="{{route('page_list')}}" class="nav-link {{ Request::is('appza/page/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.pageList')}}</span></a>
                    <a href="{{route('scope_list')}}" class="nav-link {{ Request::is('appza/page/scope/*') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.scopeList')}}</span></a>
                </li>
            </ul>
        </div>


        <div class="nav-group {{ Request::is('appza/component-group/*') ? 'show' : ''}}">
            <div class="nav-group-label" style="font-size: 15px !important;">{{__('messages.componentGroup')}}</div>
            <ul class="nav-sidebar">
                <li class="nav-item ">
                    <a href="{{route('component_group_list')}}" class="nav-link {{ Request::is('appza/component-group/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.componentGroupList')}}</span></a>
                    <a href="{{route('component_group_add')}}" class="nav-link {{ Request::is('appza/component-group/create') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.createComponentType')}}</span></a>
                </li>
            </ul>
        </div>


        <div class="nav-group {{ Request::is('appza/component/*') ? 'show' : ''}}">
            <div class="nav-group-label" style="font-size: 15px !important;">{{__('messages.component')}}</div>
            <ul class="nav-sidebar">
                <li class="nav-item ">
                    <a href="{{route('component_list')}}" class="nav-link {{ Request::is('appza/component/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.componentList')}}</span></a>
                    <a href="{{route('component_add')}}" class="nav-link {{ Request::is('appza/component/create') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.createComponent')}}</span></a>
                </li>
            </ul>
        </div>


        <div class="nav-group {{ Request::is('appza/global-config/*') ? 'show' : ''}}">
            <div class="nav-group-label" style="font-size: 15px !important;">{{__('messages.GlobalConfig')}}</div>
            <ul class="nav-sidebar">
                <li class="nav-item ">
                    <a href="{{route('global_config_list')}}" class="nav-link {{ Request::is('appza/global-config/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.GlobalConfigList')}}</span></a>
                </li>
            </ul>
        </div>



        <div class="nav-group {{ Request::is('appza/theme/*') ? 'show' : ''}}">
            <div class="nav-group-label" style="font-size: 15px !important;">{{__('messages.Theme')}}</div>
            <ul class="nav-sidebar">
                <li class="nav-item ">
                    <a href="{{route('theme_list')}}" class="nav-link {{ Request::is('appza/theme/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.themeList')}}</span></a>

                    <a href="{{route('theme_sort')}}" class="nav-link {{ Request::is('appza/theme/sort') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('messages.themeSort')}}</span></a>
                </li>
            </ul>
        </div>


            {{--<div class="nav-group {{ Request::is(app()->getLocale().'/appfiy/apk/*') ? 'show' : ''}}">
                <div class="nav-group-label" style="font-size: 15px !important;">{{__('appfiy::messages.ApkBuildHistory')}}</div>
                <ul class="nav-sidebar">
                    <li class="nav-item ">
                        <a href="{{route('apk_list',app()->getLocale())}}" class="nav-link {{ Request::is(app()->getLocale().'/appfiy/apk/list') ? 'active' : ''}}"><i data-feather="arrow-right"></i><span>{{__('appfiy::messages.apkList')}}</span></a>
                    </li>
                </ul>
            </div>--}}

    </div><!-- sidebar-body -->


    <div class="sidebar-footer">
        <a href="" class="avatar online"><span class="avatar-initial" style="font-size: 15px;">
                <img src="{{asset('Fav.svg')}}" alt="">
            </span></a>
        <div class="avatar-body">
            <div class="d-flex align-items-center justify-content-between">
                <h6>{{auth()->user()?auth()->user()->name:''}}</h6>
                <a href="" class="footer-menu"><i class="ri-settings-4-line"></i></a>
            </div>
            <span>
                {{--@php
                    $roles = auth()->user()->getRoleNames();
                    foreach ($roles as $role){
                        if (!empty($role)){
                            echo $role.' <br> ';
                        }
                    }
                @endphp--}}
            </span>
        </div><!-- avatar-body -->
    </div><!-- sidebar-footer -->
</div>

