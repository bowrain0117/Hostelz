<template>
  <div class="listing-comparison container mb-5">
    <h1 class="cl-dark font-weight-bold title-2 mb-4">Hostel Comparizon Tool</h1>

    <div class="row">
      <div class="col-lg-4 col-sm-6 mb-4 hover-animate comparison-card" v-for="listing in listings">

        <hostel-card :listing="listing"></hostel-card>

        <div class="listing-remove-button" @click="removeListing(listing.id)">
          <svg-icon :width="24" :height="24" :icon="'black-restriction'"></svg-icon>
        </div>

      </div>

    </div>

  </div>
</template>

<script>
import { mapActions, mapMutations, mapState } from "vuex";

export default {
  name: 'HostelCards',
  computed: {
    ...mapState('comparisonModule', [
      'listings',
    ]),
  },
  methods: {
    ...mapMutations('comparisonModule', [
      'set',
    ]),
    ...mapActions('comparisonModule', [
      'removeListingFromCompare',
    ]),
    removeListing(id) {
      this.removeListingFromCompare(id)
    },
  },
}
</script>

<style scoped>
.comparison-card {
  position: relative;
}
.listing-remove-button {
  display: none;
}

.comparison-card:hover .listing-remove-button {
  display: unset;
  cursor: pointer;
  color: #000;
  position: absolute;
  right: 25px;
  top: 10px;
}

.comparison-img {
  width: 240px;
  height: 170px;
}
</style>