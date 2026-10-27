@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="pagination-container">
        <div class="pagination-info">
            Menampilkan <span class="font-medium">{{ $paginator->firstItem() }}</span> s.d. <span class="font-medium">{{ $paginator->lastItem() }}</span> dari total <span class="font-medium">{{ $paginator->total() }}</span> aktivitas
        </div>

        <div class="pagination-links">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn disabled" aria-disabled="true" aria-label="Halaman Sebelumnya">
                    ‹
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn" aria-label="Halaman Sebelumnya">
                    ‹
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination-btn disabled">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-btn active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-btn" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn" aria-label="Halaman Berikutnya">
                    ›
                </a>
            @else
                <span class="pagination-btn disabled" aria-disabled="true" aria-label="Halaman Berikutnya">
                    ›
                </span>
            @endif
        </div>
    </nav>
@endif
