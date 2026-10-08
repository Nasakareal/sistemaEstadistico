<li @isset($item['id']) id="{{ $item['id'] }}" @endisset
    class="nav-item has-treeview {{ $item['submenu_class'] }} {{ !empty($item['force_open']) ? 'menu-open' : '' }}"
    @if(!empty($item['integrity_monitor'])) data-sv-integrity-key="{{ $item['integrity_monitor'] }}" @endif
    @if(!empty($item['force_open'])) data-sv-force-open="true" @endif>

    {{-- Menu toggler --}}
    <a class="nav-link {{ $item['class'] }} @isset($item['shift']) {{ $item['shift'] }} @endisset"
       href="" {!! $item['data-compiled'] ?? '' !!}>

        <i class="{{ $item['icon'] ?? 'far fa-fw fa-circle' }} {{
            isset($item['icon_color']) ? 'text-'.$item['icon_color'] : ''
        }}"></i>

        <p>
            {{ $item['text'] }}
            <i class="fas fa-angle-left right"></i>

            @isset($item['label'])
                <span class="badge badge-{{ $item['label_color'] ?? 'primary' }} right">
                    {{ $item['label'] }}
                </span>
            @endisset
        </p>

    </a>

    {{-- Menu items --}}
    <ul class="nav nav-treeview">
        @each('adminlte::partials.sidebar.menu-item', $item['submenu'], 'item')
    </ul>

</li>
