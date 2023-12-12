<template>
  <hostel-cards></hostel-cards>

  <hostel-features></hostel-features>
</template>

<script>
import { mapMutations, mapState } from "vuex";

export default {
  name: 'Comparison',
  props: ['listings', 'features'],
  created() {
    this.set(['features', this.features])
    this.set(['listings', this.listings])
  },
  computed: {
    ...mapState('comparisonModule', [
      'features',
      'listings',
    ]),
  },
  methods: {
    ...mapMutations('comparisonModule', [
      'set',
    ]),
  },
}
</script>

<style scoped>
</style>