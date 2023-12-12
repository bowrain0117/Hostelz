<template>
  <div id="reviewSort" class="d-flex justify-content-between mb-3 align-items-center">
    <div class="">
      <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        Sort by: <span class="review-sort-value text-capitalize">{{ this.sortOptions[sortedBy] }}</span>
        <svg-icon :width="24" :height="24" :icon="'arrow-bottom'"></svg-icon>
      </button>

      <div class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton" style="min-width: 240px;">
        <ul class="mb-0 pl-0 list-unstyled">
          <li v-for="(optionName, value) in this.sortOptions"
              class="review-sortBy-item py-2 border-bottom cursor-pointer"
              :class="{ selected: sortedBy === value}"
              :data-value="value"
              v-on:click="sort"
          >
            {{ optionName }}
            <svg-icon v-if="sortedBy === value" :width="24" :height="24" :icon="'checked-icon'"></svg-icon>
          </li>
        </ul>
      </div>
    </div>

    <div class="form-inline" style="padding-left: 1px;">
            <span class="form-group position-relative flex-grow-1 mb-0">
                <span class="position-absolute" style="left: 20px; fill: #4A5268; top: 50%; transform: translateY(-50%);">
                  <svg-icon :width="22" :height="22" :icon="'search-icon'"></svg-icon>
                </span>

                <input name="listing-reviews-search"
                       placeholder="Search for keywords"
                       v-on:keyup="keyup"
                       v-model="searchString"
                       type="text" id="listingReviewsSearch"
                       class="form-control bg-light rounded-xl border-0 cl-subtext"
                       style="padding-left: 54px;"
                />
            </span>
    </div>
  </div>
</template>

<script>
import { mapMutations, mapState, mapActions } from 'vuex'

export default {
  name: 'SearchSortReviews',
  props: [
      'sortOptions',
  ],
  data() {
    return {
      searchString: '',
    }
  },
  computed: {
    ...mapState('listingReviewsModules', [
      'sortedBy',
    ]),
  },
  methods: {
    ...mapMutations('listingReviewsModules', [
        'set',
    ]),
    ...mapActions('listingReviewsModules', [
        'getSortedReviews',
    ]),
    keyup() {
      this.set(['searchString', this.searchString]);
      this.set(['currentPage', 1]);
      this.getSortedReviews();
    },
    sort(e) {
      this.set(['sortedBy', e.target.dataset.value]);
      this.getSortedReviews();
    }
  }
}
</script>