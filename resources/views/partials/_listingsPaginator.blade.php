@if ($paginator->hasPages())
    <nav aria-label="Pagination Navigation">
        <ul class="pagination justify-content-center mb-0">

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item d-flex">
                    <a class="tx-small cl-second disabled icon-rounded icon-rounded-sm" disabled href="" aria-label="Previous">
                        @include('partials.svg-icon', ['svg_id' => 'pagination-prev', 'svg_w' => '25', 'svg_h' => '24'])
                        <span class="sr-only">Previous</span>
                    </a>
                </li>
            @else
                <li class="page-item d-flex">
                    <a class="tx-small cl-second icon-rounded icon-rounded-sm" href="{{ $paginator->previousPageUrl() }}" aria-label="Previous">
                        @include('partials.svg-icon', ['svg_id' => 'pagination-prev', 'svg_w' => '25', 'svg_h' => '24'])
                        <span class="sr-only">Previous</span>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item d-flex"><a class="tx-small cl-second icon-rounded icon-rounded-sm disabled" href="#">{{ $element }}</a></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item d-flex"><a class="tx-small text-white disabled bg-second icon-rounded icon-rounded-sm" href="#">{{ $page }}</a></li>
                        @else
                            <li class="page-item d-flex"><a class="tx-small cl-second icon-rounded icon-rounded-sm" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item d-flex">
                    <a class="icon-rounded icon-rounded-sm" href="{{ $paginator->nextPageUrl() }}" aria-label="Next">
                        @include('partials.svg-icon', ['svg_id' => 'pagination-next', 'svg_w' => '25', 'svg_h' => '24'])
                        <span class="sr-only">Next</span>
                    </a>
                </li>
            @else
                <li class="page-item disabled d-flex">
                    <a class="disabled icon-rounded icon-rounded-sm" disabled href="#" aria-label="Next">
                        @include('partials.svg-icon', ['svg_id' => 'pagination-next', 'svg_w' => '25', 'svg_h' => '24'])
                        <span class="sr-only">Next</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>
@endif
