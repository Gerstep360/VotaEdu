@props(['name' => 'ballot'])
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('book')<path d="M12 5v15M3 4c4-1 7 0 9 2 2-2 5-3 9-2v15c-4-1-7 0-9 2-2-2-5-3-9-2z"/>@break
@case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-13 5h2m4 0h2"/>@break
@case('file')<path d="M14 3H5v18h14V8zm0 0v5h5M8 12h8m-8 4h5"/>@break
@case('download')<path d="M12 3v12m-4-4 4 4 4-4M4 15v6h16v-6"/>@break
@case('home')<path d="m3 10 9-7 9 7v10H3z"/><path d="M9 20v-7h6v7"/>@break
@case('users')<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v3"/>@break
@case('chart')<path d="M4 3v18h17M9 16v-5m5 5V7m5 9V4"/>@break
@case('arrow')<path d="M5 12h14m-5-5 5 5-5 5"/>@break
@case('check')<path d="m5 12 4 4L19 6"/>@break
@case('shield')<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"/><path d="m8 12 3 3 5-6"/>@break
@case('logout')<path d="M9 4H4v16h5m5-13 5 5-5 5m-6-5h11"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('search')<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@default<path d="M4 12h16v9H4zM8 15h8m-9-7 5-5 5 5-5 5z"/>
@endswitch
</svg>
