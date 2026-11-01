@props(['name'])

<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" {{ $attributes->class(['h-5 w-5']) }}>
    @switch($name)
        @case('home')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 10.5 9-7.5 9 7.5M5.25 9.4V21h13.5V9.4M9 21v-6h6v6" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.75" />
            @break

        @case('logs')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6M9 3h6a1.5 1.5 0 0 1 1.5 1.5V6h1.25A2.25 2.25 0 0 1 20 8.25v11.5A2.25 2.25 0 0 1 17.75 22H6.25A2.25 2.25 0 0 1 4 19.75V8.25A2.25 2.25 0 0 1 6.25 6H7.5V4.5A1.5 1.5 0 0 1 9 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11h8M8 15h8M8 19h5" />
            @break

        @case('submit')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h7.25L19 8v11.25A1.75 1.75 0 0 1 17.25 21H7.5a2.5 2.5 0 0 1-2.5-2.5V6.25a2.5 2.5 0 0 1 2.5-2.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 3.75V8H19M8.5 14.25l2.25 2.25 4.75-5" />
            @break

        @case('overview')
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
            @break

        @case('alerts')
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9a6 6 0 1 0-12 0c0 7-3 7-3 7h18s-3 0-3-7ZM10 20h4" />
            @break

        @case('students')
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20M9.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM17 10.5a3.5 3.5 0 0 1 4 3.46V20" />
            @break

        @case('accessibility')
            <circle cx="12" cy="4.5" r="2" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8.5 12 11l7-2.5M12 7v14M8.5 21l3.5-7 3.5 7" />
            @break

        @case('drafts')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4.5h16v15H4zM4 14h4l1.5 2h5l1.5-2h4" />
            @break

        @case('add')
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" d="M12 8v8M8 12h8" />
            @break

        @case('more')
            <circle cx="5" cy="12" r="1.25" fill="currentColor" stroke="none" />
            <circle cx="12" cy="12" r="1.25" fill="currentColor" stroke="none" />
            <circle cx="19" cy="12" r="1.25" fill="currentColor" stroke="none" />
            @break
    @endswitch
</svg>
