<section class="py-5 py-lg-6" id="neighborhoods">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col-sm-12 text-center">
                <h3 class="title-2 mb-2 text-left text-lg-center">Neighborhoods in {{ $cityName }}</h3>
                <p class="subtitle text-sm text-danger mb-4">Looking for a specific district? Here you go:</p>

                <ul class="list-inline">
                    @foreach($items as $neighborhood)
                        <li class="list-inline-item mb-2">
                            <a href="{{ $neighborhood->path }}"
                               class="hover-animate badge badge-pill badge-light p-3 font-weight-normal"
                               target="_blank"
                            >
                                {{ $neighborhood->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>