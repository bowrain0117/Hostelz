<div id="overview">
    <section class="container py-3">
        <p><b>{{ $title }}</b></p>
        <table class="table align-middle mb-0 bg-white table-striped table-hover table-responsive-xl">
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td><span class="">{{ $item['title'] }}:</span></td>
                    <td>
                        <p class="font-weight-bold mb-1">
                            <a href="{{ $item['link'] }}" target="_blank" rel="nofollow" title="{{ $item['name'] }}">
                                {{ $item['name'] }}
                            </a>
                        </p>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
</div>