<template>
  <search-sort-reviews :sortOptions="sortOptions" />

  <reviews />

  <pagination v-if="pagesNumber > 1" />
</template>

<script>
import { mapMutations, mapState } from 'vuex'

export default {
  name: 'CommunityReviews',
  props: [
    'reviews',
    'listing',
    'sortBy',
    'sortOptions',
    'pagesNumber',
  ],
  created() {
    this.set(['sortedReviews', this.reviews])
    this.set(['sortedBy', this.sortBy])
    this.set(['listing', this.listing])
    this.set(['pagesNumber', this.pagesNumber])
  },
  computed: {
    ...mapState('listingReviewsModules', [
      'sortedReviews',
      'sortedBy',
      'searchString',
      'currentPage',
    ]),
  },
  methods: {
    ...mapMutations('listingReviewsModules', [
      'set',
    ]),
  },
}
</script>