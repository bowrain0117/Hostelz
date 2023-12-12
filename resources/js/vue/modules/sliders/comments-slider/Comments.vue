<template>
  <search @inputString="getFilteredData" />
  <div id="travelTips">
    <slider :data="filteredData" :search="searchString" />
  </div>
</template>

<script>
import Search from './Search.vue'

export default {
  name: 'Comments',
  data() {
    return {
      filteredData: this.data,
      searchString: '',
    }
  },
  components: {
    Search
  },
  props: {
    data: Array,
  },
  methods: {
    getFilteredData(searchString) {
      this.filteredData = this.data.filter((item) => item.comment.toLowerCase().includes(searchString.toLowerCase()))
      this.searchString = searchString
    }
  },
  mounted() {
    document.querySelector('#travel-tips-content').classList.remove('show')
  },
}
</script>