@if ($paginator->hasPages())
    <style>
        .foodio-pagination-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            padding: 16px 4px 6px 4px;
            margin-top: 16px;
            border-top: 1px solid #f1f5f9;
        }
        [data-theme="dark"] .foodio-pagination-container {
            border-top-color: #334155;
        }
        .foodio-pagination-info {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        [data-theme="dark"] .foodio-pagination-info {
            color: #94a3b8;
        }
        .foodio-pagination-info strong {
            color: #0f172a;
            font-weight: 700;
        }
        [data-theme="dark"] .foodio-pagination-info strong {
            color: #f8fafc;
        }
        .foodio-pagination-nav {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .foodio-page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: #475569;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: all 0.15s ease;
            user-select: none;
        }
        [data-theme="dark"] .foodio-page-btn {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }
        .foodio-page-btn:hover:not(.disabled):not(.active) {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        [data-theme="dark"] .foodio-page-btn:hover:not(.disabled):not(.active) {
            background: #0f172a;
            border-color: #475569;
            color: #f8fafc;
        }
        .foodio-page-btn.active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        }
        .foodio-page-btn.disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
            background: #f8fafc;
            border-color: #e2e8f0;
        }
        [data-theme="dark"] .foodio-page-btn.disabled {
            background: #0f172a;
            border-color: #1e293b;
        }
        .foodio-page-btn svg {
            width: 14px !important;
            height: 14px !important;
            max-width: 14px !important;
            max-height: 14px !important;
            display: inline-block;
            vertical-align: middle;
            fill: currentColor;
        }
    </style>

    <nav role="navigation" aria-label="Pagination Navigation" class="foodio-pagination-container">
        {{-- Results Counter --}}
        <div class="foodio-pagination-info">
            Showing
            @if ($paginator->firstItem())
                <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong>
            @else
                <strong>{{ $paginator->count() }}</strong>
            @endif
            of <strong>{{ $paginator->total() }}</strong> orders
        </div>

        {{-- Page Buttons --}}
        <div class="foodio-pagination-nav">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="foodio-page-btn disabled" aria-disabled="true" title="Previous page">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="foodio-page-btn" aria-label="Previous page" title="Previous page">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @endif

            {{-- Numbered Page Links --}}
            @foreach ($elements as $element)
                {{-- Separator ... --}}
                @if (is_string($element))
                    <span class="foodio-page-btn disabled" style="border: none; background: transparent;">{{ $element }}</span>
                @endif

                {{-- Array of Pages --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="foodio-page-btn active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="foodio-page-btn" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="foodio-page-btn" aria-label="Next page" title="Next page">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span class="foodio-page-btn disabled" aria-disabled="true" title="Next page">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
