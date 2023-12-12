<template>
  <div class="form-inline mb-3" style="padding-left: 1px;">
    <span class="form-group position-relative flex-grow-1">
      <span class="position-absolute" style="left: 20px; fill: #4A5268; top: 50%; transform: translateY(-50%);">
        <svg-icon :width="22" :height="22" :icon="'search-icon'"></svg-icon>
      </span>

      <input
          name="travel-tips-search"
          placeholder="Search for keywords"
          type="text"
          id="travelTipsSearch"
          v-on:keyup="keyup"
          v-model="searchString"
          class="form-control bg-light rounded-xl border-0 cl-subtext"
          style="padding-left: 54px;"
      />
    </span>
  </div>
</template>

<script>
export default {
  name: 'Search',
  data() {
    return {
      searchString: '',
    }
  },
  methods: {
    keyup() {
      this.$dispatch('inputString', this.searchString)
    }
  }
}
</script>