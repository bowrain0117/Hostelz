@if ($listing->videoEmbedHTML != '' && !in_array('isClosed', $listingViewOptions))
    <div id="video" class="border-bottom pb-3 mb-3 pb-lg-3 mb-lg-3"
         @if ($listing->videoSchema != '')  itemprop="video" itemscope itemtype="http://schema.org/VideoObject" @endif >

        @if ($listing->videoSchema != '')
            <?php $schema = json_decode($listing->videoSchema); ?>
            <meta itemprop="duration" content="{!! $schema->duration !!}" />
            <meta itemprop="uploadDate" content="{!! $schema->uploadDate !!}"/>
            <meta itemprop="thumbnailURL" content="{!! $schema->thumbnailURL !!}" />
            <meta itemprop="interactionCount" content="{!! $schema->interactionCount !!}" />
            <meta itemprop="embedURL" content="{!! $schema->embedURL !!}" />
        @endif

        <h3 class="sb-title cl-text mb-5" itemprop="name">@langGet('listingDisplay.Video') @langGet('listingDisplay.Of') {{{ $listing->name }}}</h3>

        <div class="">
            <p itemprop="description">{{ langGet('listingDisplay.VideoText', [ 'hostelName' => $listing->name ]) }}</p>
            <div class="embed-responsive embed-responsive-4by3">
                {!! $listing->videoEmbedHTML !!}
            </div>
        </div>
    </div>
@endif