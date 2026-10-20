@if ($paginator->hasPages())
    <div class="mobile-pagination">

        {{-- Tombol Prev --}}
        @if ($paginator->onFirstPage())
            <button class="mp-btn disabled">‹ Prev</button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="mp-btn">‹ Prev</a>
        @endif

        {{-- Nomor Halaman --}}
        <span class="mp-info">
            Hal {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        {{-- Tombol Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="mp-btn">Next ›</a>
        @else
            <button class="mp-btn disabled">Next ›</button>
        @endif

    </div>
@endif
