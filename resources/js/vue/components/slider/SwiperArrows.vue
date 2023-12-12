<template>
  <div class="swiper-button-prev" :class="{'hide-arrow': !showPrev}">
    <svg-icon :width="this.width" :height="this.height" :icon="'slider-arrow-prev'"
              @click="swiper.slidePrev()"></svg-icon>
  </div>
  <div class="swiper-button-next" :class="{'hide-arrow': !showNext}">
    <svg-icon :width="this.width" :height="this.height" :icon="'slider-arrow-next'"
              @click="swiper.slideNext()"></svg-icon>
  </div>
</template>

<script>
import {useSwiper} from "swiper/vue";
import SvgIcon from '../../components/Icon.vue'

export default {
  name: 'SwiperArrows',
  components: {
    SvgIcon
  },
  props: {
    width: {
      type: Number,
      default: 50,
    },
    height: {
      type: Number,
      default: 50,
    },
    showPrev: Boolean,
    showNext: Boolean,
  },
  computed: {
    styles() {
      return {
        width: `${this.width}px`,
        height: `${this.height}px`
      }
    },
  },
  setup() {
    const swiper = useSwiper()

    return {
      swiper
    }
  },
}
</script>

<style scoped>
@media (max-width: 991px) {
  .swiper-button-next,
  .swiper-button-prev {
    display: none;
  }
}

.swiper-button-prev::after,
.swiper-button-next::after {
  content: '';
}
</style>