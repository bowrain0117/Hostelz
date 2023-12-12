<template>
  <ul class="pagination justify-content-center mb-0">
    <li class="page-item d-flex">
      <a
         v-on:click="clickPrev"
         :data-value="'prev'"
         class="tx-small cl-second icon-rounded icon-rounded-sm"
         :class="{ disabled: currentPage === 1}"
         href="#communityReviews"
         aria-label="Previous"
      >
        <svg-icon :width="25" :height="24" :icon="'pagination-prev'"></svg-icon>
      </a>
    </li>
    <li class="page-item d-flex" v-for="page in pagesNumber">
      <a
          v-on:click="clickNumber"
          :data-value="page"
          class="tx-small icon-rounded icon-rounded-sm"
          :class="+page === +currentPage ? 'bg-second text-white' : 'cl-second'"
          href="#communityReviews">{{ page }}</a>
    </li>
    <li class="page-item d-flex">
      <a
          v-on:click="clickNext"
          :data-value="'next'"
          class="tx-small cl-second icon-rounded icon-rounded-sm"
          :class="{ disabled: currentPage === pagesNumber}"
          href="#communityReviews"
          aria-label="Next"
      >
        <svg-icon :width="25" :height="24" :icon="'pagination-next'"></svg-icon>
      </a>
    </li>
  </ul>
</template>

<script>
import {mapState, mapMutations, mapActions} from "vuex";

export default {
  name: 'Pagination',
  computed: {
    ...mapState('listingReviewsModules', [
      'sortedBy',
      'searchString',
      'currentPage',
      'pagesNumber'
    ]),
  },
  methods: {
    ...mapMutations('listingReviewsModules', [
      'set',
    ]),
    ...mapActions('listingReviewsModules', [
      'getSortedReviews',
    ]),
    clickNumber(e) {
      this.set(['currentPage', e.currentTarget.dataset.value]);
      this.getSortedReviews();
    },
    clickNext() {
      this.set(['currentPage', ++this.currentPage]);
      this.getSortedReviews();
    },
    clickPrev() {
      this.set(['currentPage', --this.currentPage]);
      this.getSortedReviews();
    }
  },
}
</script>