@props(['name' => '', 'class' => ''])

<svg class="ic {{ $class }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('id-card')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <circle cx="8.5" cy="11" r="2" />
            <path d="M5.5 16a3 3 0 016 0m2.5-5h4m-4 3h4" />
            @break
        @case('passport')
            <rect x="5" y="2.5" width="14" height="19" rx="2" />
            <circle cx="12" cy="10" r="4" />
            <path d="M8 10h8m-4-4a7 7 0 010 8m-5 5h10" />
            @break
        @case('driving')
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M5 14h2l1.5-4h5l2 4h3l1 2v1h-2m-11 0H5v-1l.5-2m3.5 3h5" />
            <circle cx="9" cy="17" r="1.5" />
            <circle cx="16" cy="17" r="1.5" />
            @break
        @case('academic')
            <path d="M6 3h9l4 4v8" />
            <path d="M15 3v5h5M6 3a2 2 0 00-2 2v14a2 2 0 002 2h6m-7-13h6m-6 4h5" />
            <circle cx="17" cy="17" r="4" />
            <path d="M15.5 20.7l-.5 2 2-1 2 1-.5-2" />
            @break
        @case('document')
            <path d="M6 3h8l5 5v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z" />
            <path d="M14 3v5h5M8 13h8m-8 4h8" />
            @break
        @case('land-title')
            <path d="M5 3h9l5 5v5M14 3v5h5M5 3a2 2 0 00-2 2v14a2 2 0 002 2h6" />
            <path d="M4 12h5m-5 4h4m6-2l4-3 4 3v6h-8v-6zm3 6v-4h2v4" />
            @break
        @case('student')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="M5.5 16a3 3 0 016 0m2.5-5h4m-4 3h4M7 9l3-1.5L13 9l-3 1.5L7 9zm6 0v2" />
            @break
        @case('card')
            <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            @break
        @case('folder')
            <path d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
            @break
        @case('search')
            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            @break
        @case('location')
            <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            @break
        @case('calendar')
            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            @break
        @case('shield')
            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            @break
        @case('pencil')
            <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            @break
        @case('scale')
            <path d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
            @break
        @case('users')
            <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            @break
        @case('lock')
            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            @break
        @case('phone')
            <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            @break
        @case('camera')
            <path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            @break
        @case('check')
            <path d="M5 13l4 4L19 7" />
            @break
        @case('x')
            <path d="M6 18L18 6M6 6l12 12" />
            @break
    @endswitch
</svg>
