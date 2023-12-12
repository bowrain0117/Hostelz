@props(['pic', 'alt'])

@php
    if($pic === null) {
        return;
    }
@endphp

<picture class="w-100">
    <source media = "(max-width:450px)" data-srcset="<?php echo $pic->url('webp_thumbnails'); ?>" type="image/webp">
    <source media = "(max-width:450px)" data-srcset="<?php echo $pic->url('thumbnails'); ?>" type="image/jpeg">
    <source media = "(max-width:992px)" data-srcset="<?php echo $pic->url('webp_medium'); ?>" type="image/webp">
    <source media = "(max-width:992px)" data-srcset="<?php echo $pic->url('medium'); ?>" type="image/jpeg">
    <source media = "(min-width:993px)" data-srcset="<?php echo $pic->url('webp_thumbnails'); ?>" type="image/webp">
    <source media = "(min-width:993px)" data-srcset="<?php echo $pic->url('thumbnails'); ?>" type="image/jpeg">
    <img class="lazyload blur-up w-100"
         src="<?php echo $pic->url('tiny'); ?>"
         data-src="<?php echo $pic->url('thumbnails'); ?>"
         alt="{{ $alt }}">
</picture>
