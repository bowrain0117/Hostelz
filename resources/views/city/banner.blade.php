<section class="container mb-lg-2">
    <div class="row">
        <div role="alert" class="alert alert-success alert-dismissible fade show w-100 sb-title">
            @langGet('global.bannertext', ['city' => ($cityInfo) ? $cityInfo->translation()->city : '' ])
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
    </div>
</section>