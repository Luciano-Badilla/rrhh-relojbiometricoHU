<style>
    a {
        text-decoration: none !important;
    }
</style>
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('staff.list') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                @if (Auth::check() && Auth::user()->role_id == 2)
                    <div class="hidden sm:flex sm:space-x-8 sm:-my-px sm:ms-10">
                        <x-nav-link :href="route('staff.list')" :active="request()->routeIs('staff.list')">
                            {{ __('Lista del Personal') }}
                        </x-nav-link>
                    </div>
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <div class="dropdown">
                            <button
                                class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 dropdown-toggle"
                                type="button" data-toggle="dropdown" aria-expanded="false">
                                Gestiones
                            </button>
                            <ul class="dropdown-menu rounded-lg border-gray-300">
                                <x-dropdown-link :href="route('absenceReason.list')" :active="request()->routeIs('absenceReason.list')"
                                    class="no-underline block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">
                                    Just. de Inasistencias
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('areaCoordinators.list')" :active="request()->routeIs('areaCoordinators.list')"
                                    class="no-underline block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">
                                    Areas y Coordinadores
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('category.list')" :active="request()->routeIs('category.list')"
                                    class="no-underline block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">
                                    Categorías
                                </x-dropdown-link>
                            </ul>
                        </div>
                    </div>
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <div class="dropdown">
                            <button
                                class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 dropdown-toggle"
                                type="button" data-toggle="dropdown" aria-expanded="false">
                                Reportes
                            </button>
                            <ul class="dropdown-menu rounded-lg border-gray-300">
                                <x-dropdown-link :href="route('reportView.nonAttendance')" :active="request()->routeIs('reportView.nonAttendance')"
                                    class="no-underline block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">
                                    Inasistencias
                                </x-dropdown-link>
                            </ul>
                        </div>
                    </div>
                @endif

            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="dropdown">
                    <button
                        class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 dropdown-toggle"
                        type="button" data-toggle="dropdown" aria-expanded="false">
                        {{ Auth::user()->name . ' - ' . Auth::user()->role->name }}
                    </button>
                    <ul class="dropdown-menu rounded-lg w-full border-gray-300">
                        <x-dropdown-link :href="route('profile.edit')"
                            class="no-underline block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">
                            Perfil
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                                class="no-underline block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">
                                {{ __('Cerrar Sesión') }}
                            </x-dropdown-link>
                        </form>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="block sm:hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <x-nav-link :href="route('staff.list')" :active="request()->routeIs('staff.list')">
                {{ __('Lista del Personal') }}
            </x-nav-link>
        </div>
    </div>
</nav>
