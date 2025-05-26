<!-- Menu -->
  <aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
    <div class="container-xxl d-flex h-100">
      <ul class="menu-inner pb-2 pb-xl-0">
        <!-- Dashboards -->
        <li class="menu-item @if(Request::segment(1) == 'dashboard') active @endif">
          <a href="{{ route('dashboard') }}" class="menu-link">
            <i class="menu-icon fas fa-home"></i>
            <div>{{ __('message.Dashboard') }}</div>
          </a>
        </li>

        @canany(['role-list','users'])
        <!-- Users -->
        <li class="menu-item @if(Request::segment(1) == 'roles' || Request::segment(1) == 'users') active @endif">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon fa-solid fa-users"></i>
            <div>{{ __('message.users') }}</div>
          </a>

          <ul class="menu-sub">
            @can('role-list')
            <li class="menu-item @if(Request::segment(1) == 'roles') active @endif">
              <a href="{{ route('roles.index') }}" class="menu-link">
                <i class="menu-icon fas fa-lock"></i>
                <div>{{ __('message.Role') }}</div>
              </a>
            </li>
            @endcan
            @can('users-list')
            <li class="menu-item @if(Request::segment(1) == 'users') active @endif">
              <a href="{{ route('users.index') }}" class="menu-link">
                <i class="menu-icon fa-solid fa-user-plus"></i>
                <div>{{ __('message.users') }}</div>
              </a>
            </li>
            @endcan
          </ul>
        </li>
        @endcanany
      </ul>
    </div>
  </aside>
<!-- / Menu -->